<?php

namespace App\Services\WhatsApp;

use App\Models\Bill;
use App\Models\Customer;
use App\Services\InvoiceAccessLinkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppInvoiceService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function sendInvoice(Bill $bill): bool
    {
        $bill->loadMissing('customer');

        if ($bill->whatsapp_invoice_status !== 'pending' || filled($bill->whatsapp_invoice_message_id)) {
            return in_array($bill->whatsapp_invoice_status, ['accepted', 'sent', 'delivered', 'read'], true);
        }

        if (! $bill->customer?->whatsapp_invoice_opt_in) {
            $bill->forceFill([
                'whatsapp_invoice_status' => 'skipped',
                'whatsapp_invoice_error' => null,
            ])->save();

            return false;
        }

        $mobile = Customer::normalizeMobile((string) $bill->customer->mobile);
        if (! preg_match('/^[6-9]\d{9}$/', $mobile)) {
            $this->markFailed($bill, 'Customer mobile number is invalid.');

            return false;
        }

        // Claim the send before making the network request. A repeated or concurrent
        // request cannot submit another invoice once an attempt has started.
        $claimed = DB::transaction(function () use ($bill): bool {
            $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->id);
            if ($lockedBill->whatsapp_invoice_status !== 'pending' || filled($lockedBill->whatsapp_invoice_message_id)) {
                return false;
            }

            $lockedBill->forceFill([
                'whatsapp_invoice_status' => 'sending',
                'whatsapp_invoice_error' => null,
            ])->save();

            return true;
        });

        if (! $claimed) {
            return in_array($bill->fresh()->whatsapp_invoice_status, ['accepted', 'sent', 'delivered', 'read'], true);
        }

        try {
            $invoiceUrl = app(InvoiceAccessLinkService::class)->issue($bill);
            $result = $this->whatsapp->sendTemplate('91'.$mobile, [
                $bill->customer->name,
                $bill->invoice_number,
                number_format((float) $bill->grand_total, 2),
                $invoiceUrl,
            ]);
        } catch (\Throwable $exception) {
            Log::error('WhatsApp invoice send failed unexpectedly.', [
                'bill_id' => $bill->id,
                'exception_class' => get_class($exception),
            ]);
            $this->markFailed($bill, 'WhatsApp invoice request failed unexpectedly ('.class_basename($exception).').');

            return false;
        }

        if (! ($result['success'] ?? false)) {
            $this->markFailed($bill, $result['error'] ?? 'WhatsApp API request failed.');

            return false;
        }

        $bill->forceFill([
            'whatsapp_invoice_status' => 'accepted',
            'whatsapp_invoice_sent_at' => now(),
            'whatsapp_invoice_message_id' => $result['message_id'],
            'whatsapp_invoice_error' => null,
        ])->save();

        return true;
    }

    private function markFailed(Bill $bill, string $error): void
    {
        $bill->forceFill([
            'whatsapp_invoice_status' => 'failed',
            'whatsapp_invoice_error' => mb_substr($error, 0, 500),
        ])->save();

        Log::warning('WhatsApp invoice delivery failed.', ['bill_id' => $bill->id]);
    }
}

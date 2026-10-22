<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WhatsAppWebhookController extends Controller
{
    public function webhook(Request $request)
    {
        // Meta webhook verification
        if ($request->isMethod('GET')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');
            $verifyToken = (string) config('services.whatsapp.verify_token');

            if ($verifyToken === '') {
                return response('Webhook verification is not configured', 503);
            }

            if (
                $mode === 'subscribe' &&
                is_string($token) && hash_equals($verifyToken, $token) && is_string($challenge)
            ) {
                return response($challenge, 200)
                    ->header('Content-Type', 'text/plain');
            }

            return response('Forbidden', 403);
        }

        $appSecret = (string) config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        if ($appSecret === '' || ! preg_match('/^sha256=[a-f0-9]{64}$/i', $signature)) {
            return response()->json(['status' => 'unauthorized'], $appSecret === '' ? 503 : 401);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);
        if (! hash_equals($expected, $signature)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->json()->all();
        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                foreach (data_get($change, 'value.statuses', []) as $status) {
                    if (is_array($status)) {
                        $this->updateInvoiceStatus($status);
                    }
                }
            }
        }

        // Always acknowledge Meta quickly.
        return response()->json(['status' => 'ok'], 200);
    }

    private function updateInvoiceStatus(array $status): void
    {
        $messageId = data_get($status, 'id');
        $nextStatus = data_get($status, 'status');
        if (! is_string($messageId) || ! in_array($nextStatus, ['sent', 'delivered', 'read', 'failed'], true)) {
            return;
        }

        $eventTimestamp = data_get($status, 'timestamp');
        $eventEpoch = is_numeric($eventTimestamp) ? (int) $eventTimestamp : null;
        $eventAt = $eventEpoch !== null
            ? Carbon::createFromTimestampUTC($eventEpoch)->setTimezone((string) config('app.timezone', 'UTC'))
            : null;
        $errorCode = data_get($status, 'errors.0.code');

        DB::transaction(function () use ($messageId, $nextStatus, $eventAt, $eventEpoch, $errorCode): void {
            $bill = Bill::query()->where('whatsapp_invoice_message_id', $messageId)->lockForUpdate()->first();
            if (! $bill) {
                return;
            }

            $rank = ['accepted' => 0, 'sending' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4];
            $currentRank = $rank[$bill->whatsapp_invoice_status] ?? -1;
            $nextRank = $rank[$nextStatus] ?? -1;

            $currentEventEpoch = $bill->whatsapp_invoice_status_at?->timestamp;
            if ($eventEpoch !== null && $currentEventEpoch !== null) {
                if ($eventEpoch < $currentEventEpoch
                    || ($eventEpoch === $currentEventEpoch && $nextRank <= $currentRank)) {
                    return;
                }
            } elseif ($nextStatus === 'failed' && in_array($bill->whatsapp_invoice_status, ['delivered', 'read'], true)) {
                return;
            } elseif ($nextStatus !== 'failed' && $nextRank < $currentRank) {
                return;
            }

            $bill->forceFill([
                'whatsapp_invoice_status' => $nextStatus,
                'whatsapp_invoice_status_at' => $eventAt,
                'whatsapp_invoice_error' => $nextStatus === 'failed'
                    ? 'Meta reported message failure'.(is_numeric($errorCode) ? ' (code '.(int) $errorCode.')' : '').'.'
                    : null,
            ])->save();

            Log::info('WhatsApp invoice status updated.', [
                'bill_id' => $bill->id,
                'status' => $nextStatus,
                'error_code' => is_numeric($errorCode) ? (int) $errorCode : null,
            ]);
        });
    }
}

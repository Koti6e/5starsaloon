<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppInvoiceService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.access_token' => 'test-access-token',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.api_version' => 'v26.0',
            'services.whatsapp.invoice_template' => 'salon_invoice',
            'services.whatsapp.invoice_template_language' => 'en',
            'app.url' => 'https://5star.sushako.in',
        ]);
    }

    public function test_cash_payment_sends_once_to_normalized_indian_number_and_records_acceptance(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.cash']]], 200)]);
        $bill = $this->bill('cash', 'cash-invoice', true);

        $this->assertTrue($bill->customer->whatsapp_invoice_opt_in);
        Http::assertSentCount(1);
        $this->assertSame('accepted', $bill->fresh()->whatsapp_invoice_status);
        $this->assertSame('wamid.cash', $bill->fresh()->whatsapp_invoice_message_id);
        Http::assertSent(fn ($request) => $request['to'] === '919876543210'
            && data_get($request->data(), 'template.name') === 'salon_invoice'
            && data_get($request->data(), 'template.language.code') === 'en'
            && count(data_get($request->data(), 'template.components.0.parameters', [])) === 4
            && data_get($request->data(), 'template.components.0.parameters.2.text') === '500.00'
            && str_starts_with(data_get($request->data(), 'template.components.0.parameters.3.text'), 'https://5star.sushako.in/invoices/'));
        $sentRequest = Http::recorded()->first()[0];
        $invoiceUrl = data_get($sentRequest->data(), 'template.components.0.parameters.3.text');
        $invoiceToken = basename((string) parse_url($invoiceUrl, PHP_URL_PATH));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $invoiceToken);
        $this->assertSame(hash('sha256', $invoiceToken), $bill->fresh()->invoice_access_token_hash);
        $this->assertNotSame($invoiceToken, $bill->fresh()->invoice_public_token);
        $publicInvoice = $this->get(route('invoice.public', $invoiceToken))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('no-store', $publicInvoice->headers->get('cache-control'));
        $this->assertSame('no-referrer', $publicInvoice->headers->get('referrer-policy'));
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($bill->fresh()->invoice_pdf_path));
        Http::assertSentCount(1);
    }

    public function test_invoice_links_expire_revoke_and_reject_tampering(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.secure']]], 200)]);
        $bill = $this->bill('cash', 'secure-link', true);
        $sentRequest = Http::recorded()->first()[0];
        $url = data_get($sentRequest->data(), 'template.components.0.parameters.3.text');
        $token = basename((string) parse_url($url, PHP_URL_PATH));

        $this->get(route('invoice.public', str_repeat('0', 64)))->assertNotFound();
        $tamperedToken = substr_replace($token, $token[5] === '0' ? '1' : '0', 5, 1);
        $this->get(route('invoice.public', $tamperedToken))->assertNotFound();
        $bill->forceFill(['invoice_access_expires_at' => now()->subSecond()])->save();
        $this->get(route('invoice.public', $token))->assertNotFound();
        $bill->forceFill(['invoice_access_expires_at' => now()->addDay(), 'invoice_access_revoked_at' => now()])->save();
        $this->get(route('invoice.public', $token))->assertNotFound();
    }

    public function test_administrator_can_revoke_an_invoice_link(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.revoke']]], 200)]);
        $bill = $this->bill('cash', 'revoke-link', true);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $this->actingAs($admin)->post(route('admin.billing.invoice-link.revoke', $bill))->assertRedirect();
        $this->assertNotNull($bill->fresh()->invoice_access_revoked_at);
    }

    public function test_upi_card_and_split_payments_each_trigger_one_send_attempt(): void
    {
        $ids = ['wamid.upi', 'wamid.card', 'wamid.split'];
        Http::fake(['graph.facebook.com/*' => function () use (&$ids) {
            return Http::response(['messages' => [['id' => array_shift($ids)]]], 200);
        }]);

        $this->bill('upi', 'upi-invoice', true, '9876543211');
        $this->bill('card', 'card-invoice', true, '9876543212');
        $split = $this->bill('split', 'split-invoice', true, '9876543213');

        $this->assertSame(['cash', 'upi'], $split->payments->pluck('payment_method')->all());
        Http::assertSentCount(3);
    }

    public function test_missing_credentials_do_not_undo_paid_bill_and_are_visible(): void
    {
        config(['services.whatsapp.access_token' => null]);
        Http::fake();

        $bill = $this->bill('cash', 'missing-token', true);

        $this->assertSame('paid', $bill->fresh()->payment_status);
        $this->assertSame('failed', $bill->fresh()->whatsapp_invoice_status);
        Http::assertNothingSent();
    }

    public function test_authentication_rejection_is_recorded_without_retrying(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 190]], 401)]);
        $bill = $this->bill('cash', 'auth-rejected', true);

        $this->assertSame('paid', $bill->fresh()->payment_status);
        $this->assertSame('failed', $bill->fresh()->whatsapp_invoice_status);
        $this->assertStringContainsString('authentication/permission error', $bill->fresh()->whatsapp_invoice_error);
        Http::assertSentCount(1);
    }

    public function test_invalid_recipient_or_parameter_count_is_rejected_before_api_call(): void
    {
        Http::fake();
        $sender = app(WhatsAppService::class);

        $this->assertFalse($sender->sendTemplate('not-a-phone-number', ['a', 'b', 'c', 'd'])['success']);
        $this->assertFalse($sender->sendTemplate('919876543210', ['only', 'three', 'values'])['success']);
        Http::assertNothingSent();
    }

    public function test_without_recorded_consent_no_message_is_sent(): void
    {
        Http::fake();
        $bill = $this->bill('cash', 'no-consent', false);

        $this->assertSame('skipped', $bill->fresh()->whatsapp_invoice_status);
        Http::assertNothingSent();
    }

    public function test_rate_limit_is_retried_once_but_permanent_template_error_is_not(): void
    {
        $sequence = Http::sequence()
            ->push(['error' => ['code' => 4]], 429)
            ->push(['messages' => [['id' => 'wamid.after-limit']]], 200)
            ->push(['error' => ['code' => 132001]], 400);
        Http::fake(['graph.facebook.com/*' => $sequence]);

        $accepted = $this->bill('cash', 'retry-429', true, '9876543210', 'wamid.after-limit');
        $failed = $this->bill('upi', 'no-retry-400', true, '9876543211', 'wamid.unused');

        $this->assertSame('accepted', $accepted->fresh()->whatsapp_invoice_status);
        $this->assertSame('failed', $failed->fresh()->whatsapp_invoice_status);
        Http::assertSentCount(3);
    }

    public function test_validation_failure_and_duplicate_submission_do_not_send_twice(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.once']]], 200)]);
        $staff = $this->staff();
        $service = $this->service();
        $payload = [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Demo Customer',
            'whatsapp_invoice_opt_in' => 1,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'unique-invoice',
        ];

        $this->actingAs($staff)->post(route('staff.billing.store'), [...$payload, 'items' => []])->assertSessionHasErrors('items');
        $this->actingAs($staff)->post(route('staff.billing.store'), $payload)->assertRedirect();
        $this->actingAs($staff)->post(route('staff.billing.store'), $payload)->assertRedirect();

        Http::assertSentCount(1);
        $this->assertSame(1, Bill::query()->count());
    }

    public function test_billing_controller_invokes_invoice_service_after_commit(): void
    {
        $staff = $this->staff();
        $service = $this->service();
        $sender = \Mockery::mock(WhatsAppInvoiceService::class);
        $sender->shouldReceive('sendInvoice')->once()->andReturn(true);
        $this->app->instance(WhatsAppInvoiceService::class, $sender);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Demo Customer',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'controller-sender-invocation',
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_webhook_updates_only_matching_message_after_valid_signature(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.webhook']]], 200)]);
        $bill = $this->bill('cash', 'webhook-invoice', true, '9876543210', 'wamid.webhook');
        config(['services.whatsapp.app_secret' => 'test-app-secret']);
        $body = json_encode(['entry' => [[
            'changes' => [['value' => ['statuses' => [[
                'id' => 'wamid.webhook', 'status' => 'delivered', 'recipient_id' => 'redacted',
            ]]]]],
        ]]], JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'test-app-secret');

        $this->call('POST', '/api/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body)->assertOk();
        $this->assertSame('delivered', $bill->fresh()->whatsapp_invoice_status);

        $this->call('POST', '/api/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.str_repeat('0', 64),
        ], $body)->assertUnauthorized();
        $this->assertSame('delivered', $bill->fresh()->whatsapp_invoice_status);
    }

    public function test_webhook_ignores_duplicate_and_out_of_order_status_events(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ordered']]], 200)]);
        $bill = $this->bill('cash', 'ordered-webhook', true, '9876543210', 'wamid.ordered');
        config(['services.whatsapp.app_secret' => 'test-app-secret']);

        $this->postStatus('wamid.ordered', 'delivered', 1_797_000_020);
        $this->postStatus('wamid.ordered', 'delivered', 1_797_000_020);
        $this->postStatus('wamid.ordered', 'read', 1_797_000_030);
        $this->postStatus('wamid.ordered', 'failed', 1_797_000_010, 132000);

        $this->assertSame('read', $bill->fresh()->whatsapp_invoice_status, json_encode([
            'status_at' => $bill->fresh()->whatsapp_invoice_status_at?->toIso8601String(),
            'status_epoch' => $bill->fresh()->whatsapp_invoice_status_at?->timestamp,
        ], JSON_THROW_ON_ERROR));
        $this->assertSame(1_797_000_030, $bill->fresh()->whatsapp_invoice_status_at->timestamp);
    }

    public function test_latest_failed_webhook_event_is_saved_without_exposing_recipient_data(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.failed']]], 200)]);
        $bill = $this->bill('cash', 'failed-webhook', true, '9876543210', 'wamid.failed');
        config(['services.whatsapp.app_secret' => 'test-app-secret']);

        $this->postStatus('wamid.failed', 'failed', 1_797_000_040, 131026);

        $this->assertSame('failed', $bill->fresh()->whatsapp_invoice_status);
        $this->assertStringContainsString('131026', $bill->fresh()->whatsapp_invoice_error);
    }

    private function postStatus(string $messageId, string $status, int $timestamp, ?int $errorCode = null): void
    {
        $event = ['id' => $messageId, 'status' => $status, 'timestamp' => (string) $timestamp];
        if ($errorCode !== null) {
            $event['errors'] = [['code' => $errorCode]];
        }
        $body = json_encode(['entry' => [[
            'changes' => [['value' => ['statuses' => [$event]]]],
        ]]], JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'test-app-secret');

        $this->call('POST', '/api/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body)->assertOk();
    }

    private function bill(string $method, string $key, bool $optIn, string $mobile = '9876543210', ?string $messageId = 'wamid.test'): Bill
    {
        $staff = $this->staff();
        $service = $this->service();
        $payload = [
            'customer_mobile' => $mobile,
            'customer_name' => 'Demo Customer',
            'whatsapp_invoice_opt_in' => $optIn ? 1 : 0,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => $method,
            'idempotency_key' => $key,
        ];
        if ($method === 'split') {
            $payload['split_payments'] = [
                ['method' => 'cash', 'amount' => '250'],
                ['method' => 'upi', 'amount' => '250'],
            ];
        }

        $response = $this->actingAs($staff)->post(route('staff.billing.store'), $payload);
        $response->assertRedirect()->assertSessionHasNoErrors();

        return Bill::query()->where('idempotency_key', $key)->with('payments', 'customer')->firstOrFail();
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
    }

    private function service(): Service
    {
        $suffix = (string) (Service::query()->count() + 1);
        $category = ServiceCategory::query()->create(['name' => 'Hair '.$suffix, 'slug' => 'hair-'.$suffix, 'is_active' => true]);

        return Service::query()->create([
            'category_id' => $category->id,
            'name' => 'Hair Cut',
            'slug' => 'hair-cut-'.$suffix,
            'service_code' => 'SVC-HAIR-CUT-'.$suffix,
            'short_description' => 'Hair cut',
            'price_type' => 'fixed',
            'price' => '500.00',
            'duration_minutes' => 30,
            'status' => 'active',
            'is_salon_service_available' => true,
        ]);
    }
}

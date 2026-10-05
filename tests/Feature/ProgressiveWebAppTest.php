<?php

namespace Tests\Feature;

use App\Models\CustomerNotificationCampaign;
use App\Models\CustomerPushSubscription;
use App\Models\User;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProgressiveWebAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_push_subscriptions_can_be_registered_updated_and_revoked(): void
    {
        $token = str_repeat('customer-push-token-', 5);

        $this->postJson(route('customer-push-subscriptions.store'), ['token' => $token])
            ->assertCreated()
            ->assertExactJson(['ok' => true]);

        $subscription = CustomerPushSubscription::query()->sole();
        $this->assertNull($subscription->revoked_at);
        $this->assertNotSame($token, DB::table('customer_push_subscriptions')->value('token'));

        $this->postJson(route('customer-push-subscriptions.store'), ['token' => $token])
            ->assertCreated();
        $this->assertSame(1, CustomerPushSubscription::query()->count());

        $this->deleteJson(route('customer-push-subscriptions.destroy'), ['token' => $token])
            ->assertOk()
            ->assertExactJson(['ok' => true]);
        $this->assertNotNull($subscription->fresh()->revoked_at);
        $this->assertSame('', $subscription->fresh()->token);
    }

    public function test_customer_and_staff_pages_use_their_respective_pwa_manifests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(asset('manifest.webmanifest'), false)
            ->assertSee('data-pwa-audience="customer"', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(asset('staff.webmanifest'), false)
            ->assertSee('data-pwa-audience="staff"', false);
    }

    public function test_firebase_worker_endpoint_is_safe_when_web_push_is_unconfigured(): void
    {
        config([
            'services.firebase.web_api_key' => null,
            'services.firebase.auth_domain' => null,
            'services.firebase.project_id' => null,
            'services.firebase.messaging_sender_id' => null,
            'services.firebase.app_id' => null,
        ]);

        $response = $this->get(route('firebase.messaging-sw'))->assertOk();

        $this->assertSame('application/javascript; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', $response->headers->get('Cache-Control'));
        $response->assertSee('notifications are not configured');
    }

    public function test_only_admins_can_open_customer_notification_center(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $this->actingAs($staff)
            ->get(route('admin.customer-notifications.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.customer-notifications.index'))
            ->assertOk()
            ->assertSee('Customer communications');
    }

    public function test_notification_campaign_rejects_non_allowlisted_destinations(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.customer-notifications.index'))
            ->post(route('admin.customer-notifications.store'), [
                'title' => 'Salon update',
                'body' => 'A customer message',
                'destination' => 'https://example.com',
            ])
            ->assertRedirect(route('admin.customer-notifications.index'))
            ->assertSessionHasErrors('destination');

        $this->assertSame(0, CustomerNotificationCampaign::query()->count());
    }

    public function test_admin_can_send_customer_campaign_and_records_delivery_counts(): void
    {
        $privateKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($privateKey);
        $this->assertTrue(openssl_pkey_export($privateKey, $privateKeyContents));

        config([
            'services.firebase.project_id' => 'salon-test-project',
            'services.firebase.web_api_key' => 'test-web-api-key',
            'services.firebase.auth_domain' => 'salon-test-project.firebaseapp.com',
            'services.firebase.messaging_sender_id' => '123456',
            'services.firebase.app_id' => '1:123456:web:test',
            'services.firebase.vapid_key' => 'test-vapid-key',
            'services.firebase.credentials_json' => json_encode([
                'project_id' => 'salon-test-project',
                'client_email' => 'firebase@example.test',
                'private_key' => $privateKeyContents,
            ]),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token'], 200),
            'https://fcm.googleapis.com/v1/projects/salon-test-project/messages:send' => Http::response(['name' => 'message-id'], 200),
        ]);

        foreach ([
            str_repeat('customer-device-token-', 4),
            str_repeat('another-customer-device-token-', 3),
        ] as $token) {
            CustomerPushSubscription::query()->create([
                'token' => $token,
                'token_hash' => CustomerPushSubscription::hashToken($token),
                'platform' => 'web',
                'consented_at' => now(),
                'last_seen_at' => now(),
            ]);
        }
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.customer-notifications.store'), [
                'title' => 'Salon update',
                'body' => 'Appointments are available.',
                'destination' => 'book-appointment',
            ])
            ->assertRedirect(route('admin.customer-notifications.index'));

        $campaign = CustomerNotificationCampaign::query()->sole();
        $this->assertSame(2, $campaign->recipient_count);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertSame('/book-appointment', $campaign->action_url);
        Http::assertSentCount(3);
    }

    public function test_existing_admin_appointment_push_can_keep_its_internal_destination(): void
    {
        $privateKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($privateKey);
        $this->assertTrue(openssl_pkey_export($privateKey, $privateKeyContents));
        config([
            'services.firebase.project_id' => 'salon-test-project',
            'services.firebase.credentials_json' => json_encode([
                'project_id' => 'salon-test-project',
                'client_email' => 'firebase@example.test',
                'private_key' => $privateKeyContents,
            ]),
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token'], 200),
            'https://fcm.googleapis.com/v1/projects/salon-test-project/messages:send' => Http::response(['name' => 'message-id'], 200),
        ]);

        $result = app(FirebaseCloudMessaging::class)->sendToToken(
            str_repeat('admin-device-token-', 4),
            'New Appointment',
            'A customer booked.',
            '/admin/appointments/123',
        );

        $this->assertTrue($result['sent']);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'messages:send')
            && $request['message']['data']['url'] === '/admin/appointments/123');
    }

    public function test_pwa_manifests_have_distinct_customer_and_staff_identities(): void
    {
        $customerManifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $staffManifest = json_decode(file_get_contents(public_path('staff.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertNotSame($customerManifest['id'], $staffManifest['id']);
        $this->assertSame('/', $customerManifest['scope']);
        $this->assertSame('/', $staffManifest['scope']);
        $this->assertSame('/login?app=staff', $staffManifest['start_url']);

        foreach ([$customerManifest, $staffManifest] as $manifest) {
            $iconSizes = array_column($manifest['icons'], 'sizes');
            $this->assertContains('192x192', $iconSizes);
            $this->assertContains('512x512', $iconSizes);
        }
    }
}

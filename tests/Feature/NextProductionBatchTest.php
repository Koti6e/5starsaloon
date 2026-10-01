<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class NextProductionBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_is_a_sessionless_public_xml_resource_and_robots_references_it(): void
    {
        $sitemap = $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertHeader('Cache-Control', 'max-age=3600, must-revalidate, public, s-maxage=86400');

        $this->assertSame([], $sitemap->headers->all('set-cookie'));
        $this->assertStringNotContainsString('XSRF-TOKEN', implode(';', $sitemap->headers->all('set-cookie')));
        $this->assertStringNotContainsString('session', implode(';', $sitemap->headers->all('set-cookie')));
        $xml = new \DOMDocument;
        $this->assertTrue($xml->loadXML($sitemap->getContent()));
        $this->assertSame('urlset', $xml->documentElement?->localName);
        $this->assertStringContainsString(route('services.index'), $sitemap->getContent());

        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Sitemap: https://5star.sushako.in/sitemap.xml', $robots);
    }

    public function test_customer_lookup_derives_completed_visit_history_and_last_services_from_bills(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $customer = Customer::factory()->create(['name' => 'Asha Customer', 'mobile' => '9876543210']);
        $old = $this->createBill($customer, $staff, 500, now('Asia/Kolkata')->subDays(10), 'completed', 'Haircut');
        $last = $this->createBill($customer, $staff, 750, now('Asia/Kolkata')->subDay(), 'completed', 'Facial');
        $this->createBill($customer, $staff, 300, now('Asia/Kolkata'), 'cancelled', 'Cancelled Service');

        $response = $this->actingAs($staff)->getJson(route('staff.billing.customer-lookup', ['mobile' => $customer->mobile]))->assertOk();
        $response->assertJsonPath('customer.total_visits', 2)
            ->assertJsonPath('customer.last_bill_amount', '750.00')
            ->assertJsonPath('customer.last_services.0', 'Facial')
            ->assertJsonPath('customer.history_url', null);
        $this->assertSame($customer->id, $last->customer_id);
        $this->assertSame($customer->id, $old->customer_id);
    }

    public function test_todays_bills_show_creator_names_and_grouped_counts_and_sales(): void
    {
        $admin = User::factory()->create(['name' => 'Salon Admin', 'role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create(['name' => 'Staff One', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $customer = Customer::factory()->create();
        $this->createBill($customer, $staff, 500, now('Asia/Kolkata'), 'completed', 'Cut');
        $this->createBill($customer, $staff, 700, now('Asia/Kolkata')->subMinutes(5), 'completed', 'Color');
        $this->createBill($customer, $admin, 900, now('Asia/Kolkata')->subMinutes(10), 'completed', 'Facial');

        $this->actingAs($admin)->get(route('admin.billing.create'))
            ->assertOk()
            ->assertSee('Staff One')
            ->assertSee('Salon Admin')
            ->assertSee('2 bills')
            ->assertSee('1 bills')
            ->assertSee('Created by Staff One')
            ->assertSee('Overall: 3 bills');
    }

    public function test_new_billing_form_does_not_restore_split_amounts_from_old_input(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->withSession(['_old_input' => ['split_payments' => [['method' => 'cash', 'amount' => '999.00']]]])
            ->actingAs($staff)
            ->get(route('staff.billing.create'))
            ->assertOk()
            ->assertSee("splitPayments: [{method: 'cash', amount: 0}, {method: 'upi', amount: 0}]", false)
            ->assertDontSee('999.00');
    }

    public function test_admin_customer_edit_keeps_identity_and_history_and_rejects_duplicate_mobile(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active']);
        $customer = Customer::factory()->create(['name' => 'Before Name', 'mobile' => '9876543210']);
        $other = Customer::factory()->create(['mobile' => '9876543211']);
        $bill = $this->createBill($customer, $staff, 400, now('Asia/Kolkata'), 'completed', 'Cut');

        $this->actingAs($admin)->put(route('admin.customers.update', $customer), ['name' => 'After Name', 'mobile' => '9876543212'])
            ->assertRedirect(route('admin.customers.show', $customer));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'After Name', 'mobile' => '9876543212']);
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'customer_id' => $customer->id]);
        $this->assertSame($customer->id, $bill->fresh()->customer_id);

        $this->actingAs($admin)->put(route('admin.customers.update', $customer), ['name' => 'Wrong Name', 'mobile' => $other->mobile])
            ->assertSessionHasErrors('mobile');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'After Name', 'mobile' => '9876543212']);
    }

    public function test_staff_selfie_verification_is_required_once_per_working_day(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['username' => 'Staff1', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false, 'weekly_off' => 'Sunday']);
        $this->travelTo(now('Asia/Kolkata')->setTime(10, 0));

        $this->post('/login', ['username' => 'Staff1', 'password' => 'password'])
            ->assertRedirect(route('staff.selfie.create', absolute: false));
        $this->post(route('staff.selfie.store'), ['selfie_image' => $this->selfieData()])
            ->assertRedirect(route('staff.dashboard', absolute: false));
        $attendance = StaffAttendance::query()->where('staff_id', $staff->id)->firstOrFail();
        $this->assertSame('present', $attendance->status);
        $this->assertNotNull($attendance->selfie_captured_at);

        $this->post('/logout')->assertRedirect(route('login', absolute: false));
        $this->post('/login', ['username' => 'Staff1', 'password' => 'password'])
            ->assertRedirect(route('staff.dashboard', absolute: false));
        $this->assertSame(1, StaffAttendance::query()->where('staff_id', $staff->id)->count());

        $this->post('/logout');
        $this->travel(1)->days();
        $this->post('/login', ['username' => 'Staff1', 'password' => 'password'])
            ->assertRedirect(route('staff.selfie.create', absolute: false));
        $this->assertSame(1, StaffAttendance::query()->where('staff_id', $staff->id)->count());
    }

    public function test_invalid_or_cancelled_selfie_does_not_mark_attendance(): void
    {
        $staff = User::factory()->create(['username' => 'Staff2', 'role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->post('/login', ['username' => 'Staff2', 'password' => 'password'])->assertRedirect(route('staff.selfie.create', absolute: false));

        $this->post(route('staff.selfie.store'), ['selfie_image' => ''])
            ->assertSessionHasErrors('selfie_image');
        $this->assertDatabaseCount('staff_attendances', 0);
    }

    private function createBill(Customer $customer, User $creator, int $amount, $billedAt, string $status, string $serviceName): Bill
    {
        $bill = Bill::query()->create([
            'invoice_number' => 'TEST/'.Str::uuid(),
            'customer_id' => $customer->id,
            'billed_by' => $creator->id,
            'created_by' => $creator->id,
            'subtotal' => $amount,
            'discount_amount' => 0,
            'home_visit_charge' => 0,
            'grand_total' => $amount,
            'payment_status' => 'paid',
            'status' => $status,
            'idempotency_key' => (string) Str::uuid(),
            'invoice_public_token' => (string) Str::uuid(),
            'billed_at' => $billedAt,
        ]);
        $bill->items()->create([
            'service_performed_by' => $creator->id,
            'service_name_snapshot' => $serviceName,
            'service_code_snapshot' => 'TEST',
            'category_name_snapshot' => 'Test',
            'is_package_snapshot' => false,
            'quantity' => 1,
            'unit_price' => $amount,
            'line_total' => $amount,
            'price_was_confirmed' => true,
        ]);
        return $bill;
    }

    private function selfieData(): string
    {
        return 'data:image/jpeg;base64,'.base64_encode(random_bytes(12_000));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_billing_desk(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->service();

        $this->actingAs($staff)
            ->get(route('staff.billing.create'))
            ->assertOk()
            ->assertSee('Quick Billing')
            ->assertSee('Search customer by name or mobile number')
            ->assertSee('Search / Select Other Service')
            ->assertSee('Generate Bill');
    }

    public function test_billing_form_forces_browser_navigation_to_success_response(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->service();

        $this->actingAs($staff)
            ->get(route('staff.billing.create'))
            ->assertOk()
            ->assertSee('@submit.prevent="submitBilling($event)"', false)
            ->assertSee('action="/staff/billing"', false)
            ->assertSee("lookupUrl: '/staff/billing/customer-lookup'", false)
            ->assertDontSee('action="http://localhost/staff/billing"', false)
            ->assertSee(':disabled="paymentMethod !== \'split\'"', false)
            ->assertSee('openPayment()', false)
            ->assertSee('Search / Select Other Service')
            ->assertDontSee('Add Selected Services')
            ->assertSee('window.location.assign(response.url)', false)
            ->assertSee("! response.url.includes('/billing/create')", false);
    }

    public function test_admin_can_toggle_service_favorite(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['is_featured' => false]);

        $response = $this->actingAs($admin)->patch(route('admin.services.favorite.toggle', $service));

        $response->assertOk()
            ->assertJson(['id' => $service->id, 'is_favourite' => true]);

        $this->assertDatabaseHas('services', ['id' => $service->id, 'is_featured' => 1]);

        $response = $this->actingAs($admin)->patch(route('admin.services.favorite.toggle', $service));

        $response->assertOk()
            ->assertJson(['id' => $service->id, 'is_favourite' => false]);

        $this->assertDatabaseHas('services', ['id' => $service->id, 'is_featured' => 0]);
    }

    public function test_favourite_service_is_included_in_billing_payload(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['name' => 'Instant Favorite', 'is_featured' => true]);

        $this->actingAs($staff)
            ->get(route('staff.billing.create'))
            ->assertOk()
            ->assertSee('Instant Favorite')
            ->assertSee('\u0022is_favourite\u0022:true', false);
    }

    public function test_billing_dropdown_uses_active_database_services_not_public_storefront_filter(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $hiddenCategory = ServiceCategory::query()->create([
            'name' => 'Internal Billing',
            'slug' => 'internal-billing',
            'is_active' => false,
        ]);
        $activeInternal = Service::query()->create([
            'category_id' => $hiddenCategory->id,
            'name' => 'Counter Only Service',
            'slug' => 'counter-only-service',
            'service_code' => 'SVC-COUNTER-ONLY',
            'short_description' => 'Internal active service',
            'price_type' => 'fixed',
            'price' => '200.00',
            'duration_minutes' => 20,
            'status' => 'active',
            'is_salon_service_available' => false,
        ]);
        $activePackage = Service::query()->create([
            'category_id' => $hiddenCategory->id,
            'name' => 'Billing Package',
            'slug' => 'billing-package',
            'service_code' => 'PKG-BILLING',
            'short_description' => 'Active package',
            'price_type' => 'fixed',
            'price' => '300.00',
            'duration_minutes' => 30,
            'status' => 'active',
            'is_package' => true,
            'is_salon_service_available' => true,
        ]);
        $inactive = Service::query()->create([
            'category_id' => $hiddenCategory->id,
            'name' => 'Inactive Billing Trap',
            'slug' => 'inactive-billing-trap',
            'service_code' => 'SVC-INACTIVE-TRAP',
            'short_description' => 'Inactive',
            'price_type' => 'fixed',
            'price' => '100.00',
            'duration_minutes' => 10,
            'status' => 'inactive',
            'is_salon_service_available' => true,
        ]);
        $deleted = Service::query()->create([
            'category_id' => $hiddenCategory->id,
            'name' => 'Deleted Billing Trap',
            'slug' => 'deleted-billing-trap',
            'service_code' => 'SVC-DELETED-TRAP',
            'short_description' => 'Deleted',
            'price_type' => 'fixed',
            'price' => '100.00',
            'duration_minutes' => 10,
            'status' => 'active',
            'is_salon_service_available' => true,
        ]);
        $deleted->delete();

        $response = $this->actingAs($staff)->get(route('staff.billing.create'))->assertOk();

        $response->assertSee($activeInternal->name);
        $response->assertSee($activePackage->name);
        $response->assertDontSee($inactive->name);
        $response->assertDontSee($deleted->name);
    }

    public function test_required_canonical_billing_services_are_database_driven(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $response = $this->actingAs($staff)->get(route('staff.billing.create'))->assertOk();

        foreach (['Haircut', 'Beard Trim', 'Hair Wash', 'Hair Colour', 'Hair Spa', 'Facial', 'Gold Facial', 'Diamond Facial', 'Oil Massage', 'De Tan', 'Ear Piercing'] as $serviceName) {
            $response->assertSee($serviceName);
        }
    }

    public function test_active_internal_service_can_be_billed(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $category = ServiceCategory::query()->create([
            'name' => 'Internal Billing',
            'slug' => 'internal-billing',
            'is_active' => false,
        ]);
        $service = Service::query()->create([
            'category_id' => $category->id,
            'name' => 'Counter Only Service',
            'slug' => 'counter-only-service',
            'service_code' => 'SVC-COUNTER-ONLY',
            'short_description' => 'Internal active service',
            'price_type' => 'fixed',
            'price' => '200.00',
            'duration_minutes' => 20,
            'status' => 'active',
            'is_salon_service_available' => false,
        ]);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Demo Customer',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'internal-service-bill',
        ])->assertSessionHasNoErrors();

        $bill = Bill::query()->with('items')->firstOrFail();
        $this->assertSame('200.00', $bill->grand_total);
        $this->assertSame('Counter Only Service', $bill->items->first()->service_name_snapshot);
    }

    public function test_staff_creates_bill_with_server_side_biller_and_performer(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $otherStaff = User::factory()->create(['role' => 'staff', 'status' => 'active']);
        $service = $this->service(['price' => '500.00']);

        $response = $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Asha Customer',
            'items' => [[
                'service_id' => $service->id,
                'quantity' => 2,
                'service_performed_by' => $otherStaff->id,
            ]],
            'discount_amount' => '100.00',
            'home_visit_charge' => '50.00',
            'payment_method' => 'cash',
            'idempotency_key' => 'staff-bill-1',
        ]);

        $bill = Bill::query()->with('items', 'payments', 'customer')->firstOrFail();
        $response->assertRedirect(route('staff.billing.success', $bill, false));
        $this->assertSame($staff->id, $bill->billed_by);
        $this->assertSame($staff->id, $bill->created_by);
        $this->assertSame($staff->id, $bill->items->first()->service_performed_by);
        $this->assertSame('950.00', $bill->grand_total);
        $this->assertSame('cash', $bill->payments->first()->payment_method);
        $this->assertSame('9876543210', $bill->customer->mobile);
        $this->assertMatchesRegularExpression('/^5STAR\/\d{4}\/\d{2}\/\d{6}$/', $bill->invoice_number);
    }

    public function test_browser_cash_submission_ignores_hidden_split_rows(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Demo Customer',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'split_payments' => [
                ['method' => 'cash', 'amount' => '0'],
                ['method' => 'upi', 'amount' => '0'],
            ],
            'idempotency_key' => 'cash-hidden-split',
        ])->assertSessionHasNoErrors();

        $bill = Bill::query()->with('payments')->firstOrFail();
        $this->assertSame('120.00', $bill->grand_total);
        $this->assertSame(1, $bill->payments->count());
        $this->assertSame('cash', $bill->payments->first()->payment_method);
    }

    public function test_admin_billing_post_redirects_to_success_page_after_database_commit(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $response = $this->actingAs($admin)->from(route('admin.billing.create'))->post(route('admin.billing.store'), [
            'customer_mobile' => '9000000000',
            'customer_name' => 'Demo Customer',
            'items' => [[
                'service_id' => $service->id,
                'quantity' => 1,
                'service_performed_by' => $admin->id,
            ]],
            'payment_method' => 'cash',
            'payment_note' => 'Counter paid',
            'idempotency_key' => 'admin-success-flow',
        ]);

        $bill = Bill::query()->with(['items', 'payments', 'customer', 'billedBy'])->firstOrFail();
        $response->assertRedirect(route('admin.billing.success', $bill, false));
        $this->assertSame('/admin/billing/'.$bill->id.'/success', $response->headers->get('Location'));
        $this->assertSame('5STAR/'.now('Asia/Kolkata')->format('Y/m').'/000001', $bill->invoice_number);
        $this->assertSame(1, $bill->items->count());
        $this->assertSame(1, $bill->payments->count());

        $this->actingAs($admin)
            ->get(route('admin.billing.success', $bill))
            ->assertOk()
            ->assertSee('Billing Completed Successfully')
            ->assertSee('Invoice Date & Time', false)
            ->assertSee('Demo Customer')
            ->assertSee('+91 9000000000')
            ->assertSee('Cash')
            ->assertSee('PAID')
            ->assertSee('Print Invoice')
            ->assertSee('Share WhatsApp')
            ->assertSee('New Bill');
    }

    public function test_validation_failure_returns_to_create_without_creating_bill(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);

        $this->actingAs($admin)->from(route('admin.billing.create'))->post(route('admin.billing.store'), [
            'customer_mobile' => '9000000000',
            'customer_name' => 'Demo Customer',
            'payment_method' => 'cash',
            'idempotency_key' => 'admin-invalid-flow',
        ])->assertRedirect(route('admin.billing.create'))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Bill::query()->count());
    }

    public function test_customer_lookup_returns_existing_customer(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        Customer::query()->create([
            'customer_code' => 'CUS-2026-000001',
            'name' => 'Rajesh',
            'mobile' => '9876543210',
            'status' => 'active',
        ]);

        $this->actingAs($staff)
            ->getJson(route('staff.billing.customer-lookup', ['mobile' => '9876543210']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('customer.name', 'Rajesh')
            ->assertJsonPath('customers.0.name', 'Rajesh');
    }

    public function test_customer_lookup_returns_partial_mobile_suggestions(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        Customer::factory()->create(['name' => 'Koteeswaran', 'mobile' => '9445123456']);
        Customer::factory()->create(['name' => 'Kumar', 'mobile' => '9445987654']);
        Customer::factory()->create(['name' => 'Different', 'mobile' => '9000011111']);

        $this->actingAs($staff)
            ->getJson(route('staff.billing.customer-lookup', ['q' => '9445']))
            ->assertOk()
            ->assertJsonPath('customers.0.name', 'Koteeswaran')
            ->assertJsonPath('customers.1.name', 'Kumar')
            ->assertJsonMissing(['name' => 'Different']);
    }

    public function test_customer_lookup_returns_partial_name_suggestions(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        Customer::factory()->create(['name' => 'Koteeswaran', 'mobile' => '9445123456']);
        Customer::factory()->create(['name' => 'Kumar', 'mobile' => '9445987654']);

        $this->actingAs($staff)
            ->getJson(route('staff.billing.customer-lookup', ['q' => 'Koti']))
            ->assertOk()
            ->assertJsonPath('customers.0.name', 'Koteeswaran')
            ->assertJsonMissing(['name' => 'Kumar']);
    }

    public function test_same_idempotency_key_does_not_create_duplicate_bill(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);
        $payload = [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Rajesh',
            'items' => [[
                'service_id' => $service->id,
                'quantity' => 1,
            ]],
            'payment_method' => 'cash',
            'idempotency_key' => 'same-submit',
        ];

        $this->actingAs($staff)->post(route('staff.billing.store'), $payload)->assertRedirect();
        $this->actingAs($staff)->post(route('staff.billing.store'), $payload)->assertRedirect();

        $this->assertSame(1, Bill::query()->count());
    }

    public function test_split_payment_creates_multiple_payment_records(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '500.00']);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Asha Customer',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'split',
            'split_payments' => [
                ['method' => 'cash', 'amount' => '200.00'],
                ['method' => 'upi', 'amount' => '300.00'],
            ],
            'idempotency_key' => 'split-success',
        ])->assertSessionHasNoErrors();

        $bill = Bill::query()->with('payments')->firstOrFail();
        $this->assertSame(2, $bill->payments->count());
        $this->assertSame(['cash', 'upi'], $bill->payments->pluck('payment_method')->all());
    }

    public function test_upi_and_card_payments_complete(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        foreach (['upi', 'card'] as $method) {
            $this->actingAs($staff)->post(route('staff.billing.store'), [
                'customer_mobile' => '9876543210',
                'customer_name' => 'Demo Customer',
                'items' => [['service_id' => $service->id, 'quantity' => 1]],
                'payment_method' => $method,
                'idempotency_key' => 'method-'.$method,
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(['upi', 'card'], Bill::query()
            ->with('payments')
            ->orderBy('id')
            ->get()
            ->map(fn (Bill $bill) => $bill->payments->first()->payment_method)
            ->all());
    }

    public function test_multiple_bill_items_use_server_side_totals(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);
        $secondService = Service::query()->create([
            'category_id' => $service->category_id,
            'name' => 'Neck Trim',
            'slug' => 'neck-trim',
            'service_code' => 'SVC-NECK-TRIM',
            'short_description' => 'Trim',
            'price_type' => 'fixed',
            'price' => '80.00',
            'duration_minutes' => 15,
            'status' => 'active',
            'is_salon_service_available' => true,
        ]);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Demo Customer',
            'items' => [
                ['service_id' => $service->id, 'quantity' => 2],
                ['service_id' => $secondService->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'idempotency_key' => 'multiple-items',
        ])->assertSessionHasNoErrors();

        $bill = Bill::query()->with('items')->firstOrFail();
        $this->assertSame('320.00', $bill->grand_total);
        $this->assertSame(2, $bill->items->count());
    }

    public function test_existing_customer_bill_reuses_customer_record(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-2026-000001',
            'name' => 'Old Name',
            'mobile' => '9876543210',
            'status' => 'active',
        ]);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '+91 98765 43210',
            'customer_name' => 'Updated Name',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'existing-customer',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Customer::query()->count());
        $this->assertSame($customer->id, Bill::query()->firstOrFail()->customer_id);
        $this->assertSame('Updated Name', $customer->fresh()->name);
    }

    public function test_invoice_numbers_are_monthly_sequential(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        foreach (['first-sequence', 'second-sequence'] as $key) {
            $this->actingAs($staff)->post(route('staff.billing.store'), [
                'customer_mobile' => '9876543210',
                'customer_name' => 'Demo Customer',
                'items' => [['service_id' => $service->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'idempotency_key' => $key,
            ])->assertSessionHasNoErrors();
        }

        $numbers = Bill::query()->orderBy('id')->pluck('invoice_number')->all();
        $this->assertStringEndsWith('/000001', $numbers[0]);
        $this->assertStringEndsWith('/000002', $numbers[1]);
    }

    public function test_duplicate_service_lines_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $this->actingAs($staff)->from(route('staff.billing.create'))->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Rajesh',
            'items' => [
                ['service_id' => $service->id, 'quantity' => 1],
                ['service_id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'idempotency_key' => 'duplicate-lines',
        ])->assertSessionHasErrors('items');
    }

    public function test_invoice_pdf_downloads(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Rajesh',
            'items' => [[
                'service_id' => $service->id,
                'quantity' => 1,
            ]],
            'payment_method' => 'cash',
            'idempotency_key' => 'pdf-bill',
        ]);

        $bill = Bill::query()->firstOrFail();

        $this->actingAs($staff)
            ->get(route('staff.billing.pdf', $bill))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_estimated_service_requires_confirmed_price(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price_type' => 'starting_from', 'price' => '700.00', 'minimum_price' => '700.00']);

        $this->actingAs($staff)->from(route('staff.billing.create'))->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Asha Customer',
            'items' => [[
                'service_id' => $service->id,
                'quantity' => 1,
            ]],
            'payment_method' => 'cash',
            'idempotency_key' => 'needs-confirmed-price',
        ])->assertSessionHasErrors('items.0.confirmed_price');
    }

    public function test_split_payment_must_match_grand_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '500.00']);

        $this->actingAs($admin)->from(route('admin.billing.create'))->post(route('admin.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Asha Customer',
            'items' => [[
                'service_id' => $service->id,
                'quantity' => 1,
                'service_performed_by' => $admin->id,
            ]],
            'payment_method' => 'split',
            'split_payments' => [
                ['method' => 'cash', 'amount' => '200.00'],
                ['method' => 'upi', 'amount' => '200.00'],
            ],
            'idempotency_key' => 'split-mismatch',
        ])->assertSessionHasErrors('split_payments');

        $this->assertSame(0, Bill::query()->count());
    }

    public function test_completion_page_and_invoice_actions_work(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Demo Customer',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'completion-actions',
        ]);

        $bill = Bill::query()->firstOrFail();

        $this->actingAs($staff)
            ->get(route('staff.billing.complete', $bill))
            ->assertOk()
            ->assertSee('Billing Completed Successfully')
            ->assertSee('Demo Customer')
            ->assertSee('View Invoice')
            ->assertSee('/staff/billing/'.$bill->id.'/pdf', false)
            ->assertSee('/staff/billing/'.$bill->id.'/print', false)
            ->assertSee('/staff/billing/'.$bill->id.'/whatsapp', false)
            ->assertSee('/staff/billing/create', false)
            ->assertDontSee('href="#"', false);

        $this->actingAs($staff)
            ->get(route('staff.billing.show', $bill))
            ->assertOk()
            ->assertSee('5 Star New Look Salon')
            ->assertSee('Hair Cut')
            ->assertSee('PAID')
            ->assertSee('Authorised Signature')
            ->assertSee('Thank you for choosing 5 Star New Look Salon')
            ->assertDontSee('Performer')
            ->assertDontSee('Home Service Charge')
            ->assertDontSee('₹0')
            ->assertDontSee('GST')
            ->assertDontSee('Tax');

        $this->actingAs($staff)
            ->get(route('staff.billing.print', $bill))
            ->assertOk()
            ->assertSee('window.print()', false)
            ->assertSee('bg-white')
            ->assertDontSee('Performer')
            ->assertDontSee('Home Service Charge');
    }

    public function test_staff_cannot_open_another_staff_completion_page(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $otherStaff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Demo Customer',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'owned-by-staff',
        ]);

        $this->actingAs($otherStaff)
            ->get(route('staff.billing.success', Bill::query()->firstOrFail()))
            ->assertForbidden();
    }

    public function test_whatsapp_url_contains_invoice_message(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Demo Customer',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'whatsapp-message',
        ]);

        $bill = Bill::query()->firstOrFail();
        $response = $this->actingAs($staff)->get(route('staff.billing.whatsapp', $bill));
        $location = $response->headers->get('Location');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/919876543210?text=', $location);
        $this->assertStringContainsString("Invoice:\n".$bill->invoice_number, rawurldecode($location));
        $this->assertStringContainsString("Amount Paid:\n₹120", rawurldecode($location));
        $this->assertStringContainsString('Please find your invoice attached.', rawurldecode($location));
        $this->assertStringContainsString('Thank you.', rawurldecode($location));
        $this->assertStringContainsString('Staff will manually attach the PDF.', rawurldecode($location));
    }

    public function test_payment_note_saves_without_transaction_reference(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Rajesh',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'upi',
            'payment_note' => 'Paid at counter',
            'idempotency_key' => 'note-bill',
        ])->assertRedirect();

        $payment = Bill::query()->firstOrFail()->payments()->firstOrFail();
        $this->assertSame('Paid at counter', $payment->method_note);
        $this->assertNull($payment->transaction_reference);
    }

    public function test_billing_started_from_appointment_preserves_relationship(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-2026-000777',
            'name' => 'Appointment Customer',
            'mobile' => '9876543210',
            'status' => 'active',
        ]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'confirmed',
        ]);
        $appointment->appointmentServices()->create([
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'unit_price' => '120.00',
            'duration_minutes' => 30,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.billing.create', ['appointment_id' => $appointment->id]))
            ->assertOk()
            ->assertSee('name="appointment_id" value="'.$appointment->id.'"', false)
            ->assertSee('Appointment Customer')
            ->assertSee('9876543210');

        $this->actingAs($staff)->post(route('staff.billing.store'), [
            'appointment_id' => $appointment->id,
            'customer_mobile' => '9876543210',
            'customer_name' => 'Appointment Customer',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'idempotency_key' => 'appointment-linked-bill',
        ])->assertSessionHasNoErrors();

        $bill = Bill::query()->with('appointment')->firstOrFail();
        $this->assertSame($appointment->id, $bill->appointment_id);
        $this->assertSame($appointment->booking_number, $bill->appointment_booking_number);
        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertSame($appointment->id, $bill->appointment->id);
    }

    public function test_other_payment_requires_note(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $service = $this->service(['price' => '120.00']);

        $this->actingAs($staff)->from(route('staff.billing.create'))->post(route('staff.billing.store'), [
            'customer_mobile' => '9876543210',
            'customer_name' => 'Rajesh',
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
            'payment_method' => 'other',
            'idempotency_key' => 'other-no-note',
        ])->assertSessionHasErrors('payment_note');
    }

    public function test_billing_page_has_no_quantity_number_input_or_reference_field(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->service();

        $response = $this->actingAs($staff)->get(route('staff.billing.create'))->assertOk();

        $response->assertDontSee('transaction_reference');
        $response->assertDontSee('Transaction reference');
        $response->assertDontSee('type="number" name="items', false);
    }

    private function service(array $overrides = []): Service
    {
        $category = ServiceCategory::query()->create([
            'name' => 'Hair',
            'slug' => 'hair',
            'is_active' => true,
        ]);

        return Service::query()->create([
            'category_id' => $category->id,
            'name' => 'Hair Cut',
            'slug' => 'hair-cut',
            'service_code' => 'SVC-HAIR-CUT',
            'short_description' => 'Precision hair cut',
            'price_type' => 'fixed',
            'price' => '500.00',
            'duration_minutes' => 30,
            'status' => 'active',
            'is_salon_service_available' => true,
            ...$overrides,
        ]);
    }
}

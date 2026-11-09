<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminFinancialRegistersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_bill_register_and_payment_report_share_period_and_preserve_mixed_payment_rows(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 12:00:00', 'Asia/Kolkata'));
        config(['app.timezone' => 'Asia/Kolkata']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $customer = Customer::factory()->create(['name' => 'Asha QA']);
        $bill = Bill::query()->create([
            'invoice_number' => 'TEST/REGISTER/001',
            'customer_id' => $customer->id,
            'billed_by' => $staff->id,
            'created_by' => $staff->id,
            'subtotal' => '300.00',
            'discount_amount' => '0.00',
            'home_visit_charge' => '0.00',
            'grand_total' => '300.00',
            'payment_status' => 'paid',
            'status' => 'completed',
            'billed_at' => Carbon::parse('2026-10-05 11:00:00', 'Asia/Kolkata'),
        ]);
        Payment::query()->create(['bill_id' => $bill->id, 'payment_method' => 'cash', 'amount' => '100.00', 'received_by' => $staff->id, 'paid_at' => Carbon::parse('2026-10-05 11:00:00', 'Asia/Kolkata')]);
        Payment::query()->create(['bill_id' => $bill->id, 'payment_method' => 'upi', 'amount' => '200.00', 'received_by' => $staff->id, 'paid_at' => Carbon::parse('2026-10-05 11:00:00', 'Asia/Kolkata')]);

        $query = ['period' => 'daily', 'date' => '2026-10-05'];
        $this->actingAs($admin)->get(route('admin.billing.index', $query))
            ->assertOk()
            ->assertSee('TEST/REGISTER/001')
            ->assertSee('Asha QA');

        $olderCustomer = Customer::factory()->create(['name' => 'Older Customer']);
        Bill::query()->create([
            'invoice_number' => 'TEST/REGISTER/OLD',
            'customer_id' => $olderCustomer->id,
            'billed_by' => $staff->id,
            'created_by' => $staff->id,
            'subtotal' => '50.00',
            'discount_amount' => '0.00',
            'home_visit_charge' => '0.00',
            'grand_total' => '50.00',
            'payment_status' => 'paid',
            'status' => 'completed',
            'billed_at' => Carbon::parse('2026-10-04 11:00:00', 'Asia/Kolkata'),
        ]);
        $customerResponse = $this->actingAs($admin)->get(route('admin.customers.index', $query))
            ->assertOk()
            ->assertSee('Asha QA')
            ->assertDontSee('Older Customer');
        $periodCustomer = $customerResponse->viewData('customers')->first();
        self::assertSame(1, (int) $periodCustomer->report_visit_count);
        self::assertSame('300.00', number_format((float) $periodCustomer->report_total_spent, 2, '.', ''));
        self::assertSame('2026-10-05 11:00:00', \Illuminate\Support\Carbon::parse($periodCustomer->report_last_visit)->format('Y-m-d H:i:s'));

        $this->actingAs($admin)->get(route('admin.reports.payments', [...$query, 'method' => 'cash']))
            ->assertOk()
            ->assertSee('TEST/REGISTER/001')
            ->assertSee('₹100');
        self::assertEqualsWithDelta(100.0, $this->viewDataOn($admin, 'admin.reports.payments', [...$query, 'method' => 'cash']), 0.01);
    }

    public function test_financial_registers_are_admin_only(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);

        $this->actingAs($staff)->get(route('admin.billing.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.reports.payments'))->assertForbidden();
    }

    private function viewDataOn(User $admin, string $routeName, array $query): float
    {
        $response = $this->actingAs($admin)->get(route($routeName, $query))->assertOk();

        return (float) $response->viewData('selectedTotal');
    }
}

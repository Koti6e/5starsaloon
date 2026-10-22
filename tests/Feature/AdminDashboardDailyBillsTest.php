<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminDashboardDailyBillsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-22 14:30:00', 'Asia/Kolkata'));
        config(['app.timezone' => 'Asia/Kolkata']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_today_bills_use_service_date_and_exclude_backdated_bills_recorded_today(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        $today = $this->bill('TODAY-001', '100.00', '2026-10-22 10:00:00');
        $backdated = $this->bill('BACK-001', '900.00', '2026-10-01 10:00:00');
        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->assertSame(1, $response->viewData('todayBills'));
        $this->assertSame('100.00', number_format((float) $response->viewData('todaySales'), 2, '.', ''));
        $this->assertSame([$today->id], $response->viewData('todayBillRows')->pluck('id')->all());
        $response->assertSee(htmlspecialchars(route('admin.reports.sales', ['period' => 'daily', 'date' => '2026-10-22'])), false);
        $this->assertDatabaseHas('bills', ['id' => $backdated->id, 'created_at' => now('Asia/Kolkata')->format('Y-m-d H:i:s')]);
    }

    public function test_staff_cannot_retrieve_the_admin_dashboard_bills(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $this->actingAs($staff)->get(route('admin.dashboard'))->assertForbidden();
    }

    private function bill(string $number, string $amount, string $billedAt): Bill
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active', 'must_change_password' => false]);
        return Bill::query()->create([
            'invoice_number' => $number, 'customer_id' => $customer->id, 'billed_by' => $user->id, 'created_by' => $user->id,
            'subtotal' => $amount, 'discount_amount' => 0, 'home_visit_charge' => 0, 'grand_total' => $amount,
            'payment_status' => 'paid', 'status' => 'completed', 'billed_at' => Carbon::parse($billedAt, 'Asia/Kolkata'),
        ]);
    }
}

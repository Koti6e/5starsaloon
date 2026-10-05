<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminSalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-05 12:00:00', 'Asia/Kolkata'));
        config(['app.timezone' => 'Asia/Kolkata']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_month_report_defaults_to_ist_month_and_shows_active_staff_without_sales(): void
    {
        $admin = $this->user('admin', 'Salon Admin');
        $staff = $this->user('staff', 'Priya');
        $zeroSalesStaff = $this->user('staff', 'Meena');
        $this->bill($staff, '1250.00', '2026-10-05 11:00:00');

        $this->actingAs($admin)
            ->get(route('admin.reports.sales'))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertSee('value="2026-10"', false)
            ->assertSee('₹1,250')
            ->assertSee('Priya')
            ->assertSee('Meena')
            ->assertSee('1 bill')
            ->assertSee('2');

        $this->assertDatabaseCount('bills', 1);
        $this->assertNotSame($staff->id, $zeroSalesStaff->id);
    }

    public function test_previous_month_selection_returns_only_previous_month_sales(): void
    {
        $admin = $this->user('admin', 'Salon Admin');
        $staff = $this->user('staff', 'Priya');
        $this->bill($staff, '700.00', '2026-09-30 23:59:59');
        $this->bill($staff, '900.00', '2026-10-01 00:00:00');

        $this->actingAs($admin)
            ->get(route('admin.reports.sales', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('September 2026')
            ->assertSee('value="2026-09"', false)
            ->assertSee('₹700')
            ->assertDontSee('₹1,600')
            ->assertSee('1 bill');
    }

    public function test_month_boundaries_group_sales_by_billed_by_and_reconcile_completed_bill_totals(): void
    {
        $admin = $this->user('admin', 'Salon Admin');
        $firstBiller = $this->user('staff', 'Priya');
        $secondBiller = $this->user('staff', 'Meena');

        $this->bill($firstBiller, '1500.00', '2026-10-01 00:00:00', ['created_by' => $admin->id]);
        $this->bill($firstBiller, '500.00', '2026-10-31 23:59:59', ['created_by' => $admin->id]);
        $this->bill($secondBiller, '2500.00', '2026-10-15 12:00:00', ['created_by' => $admin->id]);
        $this->bill($firstBiller, '8000.00', '2026-10-20 12:00:00', ['status' => 'cancelled']);
        $this->bill($firstBiller, '9000.00', '2026-09-30 23:59:59');
        $this->bill($secondBiller, '11000.00', '2026-11-01 00:00:00');

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.sales', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('₹4,500')
            ->assertSee('2,000')
            ->assertSee('2,500')
            ->assertSee('44.4%')
            ->assertSee('55.6%')
            ->assertSee('3 bills')
            ->assertSee('bills.billed_by')
            ->assertSee('bills.grand_total');

        $this->assertSame(3, (int) $response->viewData('totalBills'));
        $this->assertSame('4500.00', number_format((float) $response->viewData('totalSales'), 2, '.', ''));
        $this->assertSame(
            '4500.00',
            number_format((float) $response->viewData('staffRows')->sum('total_sales'), 2, '.', ''),
        );

        $salesByBiller = $response->viewData('staffRows')->keyBy('staff_name');
        $this->assertSame(2, (int) $salesByBiller->get('Priya')->bill_count);
        $this->assertSame('2000.00', number_format((float) $salesByBiller->get('Priya')->total_sales, 2, '.', ''));
        $this->assertSame(1, (int) $salesByBiller->get('Meena')->bill_count);
    }

    public function test_admin_can_open_report_but_staff_cannot(): void
    {
        $staff = $this->user('staff', 'Priya');
        $admin = $this->user('admin', 'Salon Admin');

        $this->actingAs($staff)
            ->get(route('admin.reports.sales'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.reports.sales'))
            ->assertOk()
            ->assertSee('Sales Report');
    }

    public function test_invalid_month_is_rejected_and_admin_navigation_links_to_sales_report(): void
    {
        $admin = $this->user('admin', 'Salon Admin');

        $this->actingAs($admin)
            ->from(route('admin.reports.sales'))
            ->get(route('admin.reports.sales', ['month' => '2026-13']))
            ->assertRedirect(route('admin.reports.sales'))
            ->assertSessionHasErrors('month');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sales Report')
            ->assertSee(route('admin.reports.sales'), false);
    }

    private function user(string $role, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => $role,
            'status' => 'active',
            'must_change_password' => false,
        ]);
    }

    private function bill(User $biller, string $amount, string $billedAt, array $overrides = []): Bill
    {
        $customer = Customer::factory()->create();

        return Bill::query()->create([
            'invoice_number' => 'TEST-'.str()->uuid(),
            'customer_id' => $customer->id,
            'billed_by' => $biller->id,
            'created_by' => $biller->id,
            'subtotal' => $amount,
            'discount_amount' => '0.00',
            'home_visit_charge' => '0.00',
            'grand_total' => $amount,
            'payment_status' => 'paid',
            'status' => 'completed',
            'billed_at' => Carbon::parse($billedAt, 'Asia/Kolkata'),
            ...$overrides,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-22 12:00:00', 'Asia/Kolkata'));
        config(['app.timezone' => 'Asia/Kolkata']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_weekly_and_monthly_reports_share_period_calculations(): void
    {
        $admin = $this->user('admin', 'Salon Admin');
        $staff = $this->user('staff', 'Priya');
        $cashBill = $this->bill($staff, '100.00', '2026-10-05 10:00:00', 'cash', 'Cut');
        $upiBill = $this->bill($staff, '200.00', '2026-10-06 10:00:00', 'upi', 'Color');
        $this->bill($staff, '900.00', '2026-10-05 11:00:00', 'cash', 'Canceled', 'cancelled');

        $daily = $this->actingAs($admin)
            ->get(route('admin.reports.index', ['period' => 'daily', 'date' => '2026-10-05']))
            ->assertOk()
            ->assertSee('Monday, 05 Oct 2026')
            ->assertSee('Cash Payments')
            ->assertSee('Top Services')
            ->assertSee('Staff Performance');
        $this->assertSame(1, $daily->viewData('totalBills'));
        $this->assertSame('100.00', number_format($daily->viewData('totalSales'), 2, '.', ''));
        $this->assertSame('100.00', number_format($daily->viewData('paymentTotals')['cash'], 2, '.', ''));
        $this->assertSame([$cashBill->id], $daily->viewData('recentBills')->pluck('id')->all());

        $weekly = $this->actingAs($admin)
            ->get(route('admin.reports.index', ['period' => 'weekly', 'week' => '2026-10-07']))
            ->assertOk()
            ->assertSee('05 Oct – 11 Oct 2026');
        $this->assertSame('2026-10-05', $weekly->viewData('week'));
        $this->assertSame(2, $weekly->viewData('totalBills'));
        $this->assertSame('300.00', number_format($weekly->viewData('totalSales'), 2, '.', ''));
        $this->assertSame('100.00', number_format($weekly->viewData('paymentTotals')['cash'], 2, '.', ''));
        $this->assertSame('200.00', number_format($weekly->viewData('paymentTotals')['upi'], 2, '.', ''));
        $this->assertSame(1, (int) $weekly->viewData('topServices')->firstWhere('service_name_snapshot', 'Cut')->quantity);
        $this->assertSame('300.00', number_format((float) $weekly->viewData('staffPerformance')->first()->total_sales, 2, '.', ''));

        $monthly = $this->actingAs($admin)
            ->get(route('admin.reports.index', ['period' => 'monthly', 'month' => '2026-10']))
            ->assertOk()
            ->assertSee('October 2026');
        $this->assertSame(2, $monthly->viewData('totalBills'));
        $this->assertSame('2026-10', $monthly->viewData('month'));
        $this->assertNotSame($upiBill->id, $cashBill->id);
    }

    public function test_reports_are_admin_only_and_reject_future_reporting_dates(): void
    {
        $staff = $this->user('staff', 'Priya');
        $admin = $this->user('admin', 'Salon Admin');

        $this->actingAs($staff)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($admin)->from(route('admin.reports.index'))
            ->get(route('admin.reports.index', ['period' => 'daily', 'date' => '2026-10-23']))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('date');
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

    private function bill(User $biller, string $amount, string $billedAt, string $method, string $serviceName, string $status = 'completed'): Bill
    {
        $customer = Customer::factory()->create();
        $bill = Bill::query()->create([
            'invoice_number' => 'REPORT-'.Str::uuid(),
            'customer_id' => $customer->id,
            'billed_by' => $biller->id,
            'created_by' => $biller->id,
            'subtotal' => $amount,
            'discount_amount' => '0.00',
            'home_visit_charge' => '0.00',
            'grand_total' => $amount,
            'payment_status' => 'paid',
            'status' => $status,
            'billed_at' => Carbon::parse($billedAt, 'Asia/Kolkata'),
        ]);
        $bill->items()->create([
            'service_performed_by' => $biller->id,
            'service_name_snapshot' => $serviceName,
            'service_code_snapshot' => 'TEST',
            'category_name_snapshot' => 'Test',
            'is_package_snapshot' => false,
            'quantity' => 1,
            'unit_price' => $amount,
            'line_total' => $amount,
            'price_was_confirmed' => true,
        ]);
        Payment::query()->create([
            'bill_id' => $bill->id,
            'payment_method' => $method,
            'amount' => $amount,
            'received_by' => $biller->id,
            'paid_at' => Carbon::parse($billedAt, 'Asia/Kolkata'),
        ]);

        return $bill;
    }
}

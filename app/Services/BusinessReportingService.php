<?php

namespace App\Services;

use App\Models\Bill;
use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Shared financial rules for reports: only completed bills count as sales. */
class BusinessReportingService
{
    public function completedBills(ReportingPeriod $period): Builder
    {
        return $this->completedBillsBetween($period->start, $period->endExclusive);
    }

    public function completedBillsBetween(CarbonImmutable $start, CarbonImmutable $endExclusive): Builder
    {
        return Bill::query()
            ->where('bills.status', 'completed')
            ->where('bills.billed_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('bills.billed_at', '<', $endExclusive->format('Y-m-d H:i:s'));
    }

    /**
     * @return array{gross_sales: float, discounts: float, net_sales: float, bill_count: int, cancelled_bill_count: int, average_bill: float, collections: Collection<int, object>, total_collected: float}
     */
    public function summary(ReportingPeriod $period): array
    {
        $totals = $this->completedBills($period)
            ->selectRaw('COUNT(*) as bill_count, COALESCE(SUM(subtotal + home_visit_charge), 0) as gross_sales, COALESCE(SUM(discount_amount), 0) as discounts, COALESCE(SUM(grand_total), 0) as net_sales')
            ->first();

        $cancelledBillCount = Bill::query()
            ->where('bills.status', 'cancelled')
            ->where('bills.billed_at', '>=', $period->start->format('Y-m-d H:i:s'))
            ->where('bills.billed_at', '<', $period->endExclusive->format('Y-m-d H:i:s'))
            ->count();

        $collections = DB::table('payments')
            ->join('bills', 'payments.bill_id', '=', 'bills.id')
            ->where('bills.status', 'completed')
            ->where('payments.paid_at', '>=', $period->start->format('Y-m-d H:i:s'))
            ->where('payments.paid_at', '<', $period->endExclusive->format('Y-m-d H:i:s'))
            ->selectRaw('payments.payment_method, COALESCE(SUM(payments.amount), 0) as total, COUNT(*) as count')
            ->groupBy('payments.payment_method')
            ->orderBy('payments.payment_method')
            ->get();

        $billCount = (int) $totals->bill_count;
        $netSales = (float) $totals->net_sales;

        return [
            'gross_sales' => (float) $totals->gross_sales,
            'discounts' => (float) $totals->discounts,
            'net_sales' => $netSales,
            'bill_count' => $billCount,
            'cancelled_bill_count' => $cancelledBillCount,
            'average_bill' => $billCount > 0 ? $netSales / $billCount : 0.0,
            'collections' => $collections,
            'total_collected' => (float) $collections->sum('total'),
        ];
    }

    public function customersServed(ReportingPeriod $period): int
    {
        return $this->completedBills($period)->distinct('customer_id')->count('customer_id');
    }
}

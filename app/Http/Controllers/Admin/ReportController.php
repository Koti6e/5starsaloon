<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $timezone = (string) config('app.timezone', 'Asia/Kolkata');
        $today = now($timezone)->toDateString();
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'week' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'month' => ['nullable', 'date_format:Y-m', 'before_or_equal:'.now($timezone)->format('Y-m')],
        ]);
        $period = $validated['period'] ?? (isset($validated['date']) ? 'daily' : (isset($validated['week']) ? 'weekly' : 'monthly'));
        $start = match ($period) {
            'daily' => CarbonImmutable::parse($validated['date'] ?? $today, $timezone)->startOfDay(),
            'weekly' => CarbonImmutable::parse($validated['week'] ?? $today, $timezone)->startOfWeek(CarbonImmutable::MONDAY),
            default => CarbonImmutable::createFromFormat('!Y-m', $validated['month'] ?? now($timezone)->format('Y-m'), $timezone),
        };
        $end = match ($period) {
            'daily' => $start->addDay(),
            'weekly' => $start->addWeek(),
            default => $start->addMonth(),
        };
        $billsForPeriod = fn () => Bill::query()
            ->where('status', 'completed')
            ->where('billed_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('billed_at', '<', $end->format('Y-m-d H:i:s'));
        $totalSales = (float) (clone $billsForPeriod())->sum('grand_total');
        $totalBills = (int) (clone $billsForPeriod())->count();
        $paymentBreakdown = Payment::query()
            ->join('bills', 'payments.bill_id', '=', 'bills.id')
            ->select('payments.payment_method', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('bills.status', 'completed')
            ->where('payments.paid_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('payments.paid_at', '<', $end->format('Y-m-d H:i:s'))
            ->groupBy('payments.payment_method')
            ->orderByDesc('total')
            ->get();
        $paymentTotals = collect(['cash', 'upi', 'card'])
            ->mapWithKeys(fn (string $method) => [$method => (float) ($paymentBreakdown->firstWhere('payment_method', $method)?->total ?? 0)]);

        return view('admin.reports.index', [
            'period' => $period,
            'selectedDate' => $start->toDateString(),
            'week' => $start->toDateString(),
            'month' => $start->format('Y-m'),
            'periodLabel' => match ($period) {
                'daily' => $start->format('l, d M Y'),
                'weekly' => $start->format('d M').' – '.$end->subDay()->format('d M Y'),
                default => $start->locale(app()->getLocale())->translatedFormat('F Y'),
            },
            'totalSales' => $totalSales,
            'totalBills' => $totalBills,
            'paymentBreakdown' => $paymentBreakdown,
            'paymentTotals' => $paymentTotals,
            'topServices' => BillItem::query()
                ->select('service_name_snapshot', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(line_total) as total'))
                ->whereHas('bill', fn ($query) => $query
                    ->where('status', 'completed')
                    ->where('billed_at', '>=', $start->format('Y-m-d H:i:s'))
                    ->where('billed_at', '<', $end->format('Y-m-d H:i:s')))
                ->groupBy('service_name_snapshot')
                ->orderByDesc('quantity')
                ->limit(10)
                ->get(),
            'staffPerformance' => Bill::query()
                ->join('users', 'users.id', '=', 'bills.billed_by')
                ->where('bills.status', 'completed')
                ->where('bills.billed_at', '>=', $start->format('Y-m-d H:i:s'))
                ->where('bills.billed_at', '<', $end->format('Y-m-d H:i:s'))
                ->select('users.id as staff_id', 'users.name as staff_name')
                ->selectRaw('COUNT(bills.id) as bill_count, COALESCE(SUM(bills.grand_total), 0) as total_sales')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_sales')
                ->get(),
            'recentBills' => $billsForPeriod()
                ->with(['customer', 'billedBy', 'payments'])
                ->latest('billed_at')
                ->limit(10)
                ->get(),
            'timezone' => $timezone,
            'moneyFormatter' => fn ($amount) => Money::inr($amount),
        ]);
    }
}

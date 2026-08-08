<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(): View
    {
        $today = now('Asia/Kolkata')->toDateString();
        $monthStart = now('Asia/Kolkata')->startOfMonth();

        return view('admin.reports.index', [
            'todaySales' => Bill::query()->whereDate('billed_at', $today)->sum('grand_total'),
            'todayBills' => Bill::query()->whereDate('billed_at', $today)->count(),
            'monthSales' => Bill::query()->where('billed_at', '>=', $monthStart)->sum('grand_total'),
            'monthBills' => Bill::query()->where('billed_at', '>=', $monthStart)->count(),
            'paymentBreakdown' => Payment::query()
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->where('paid_at', '>=', $monthStart)
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->get(),
            'topServices' => BillItem::query()
                ->select('service_name_snapshot', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(line_total) as total'))
                ->whereHas('bill', fn ($query) => $query->where('billed_at', '>=', $monthStart))
                ->groupBy('service_name_snapshot')
                ->orderByDesc('quantity')
                ->limit(10)
                ->get(),
            'recentBills' => Bill::query()
                ->with(['customer', 'billedBy', 'payments'])
                ->latest('billed_at')
                ->limit(10)
                ->get(),
            'moneyFormatter' => fn ($amount) => Money::inr($amount),
        ]);
    }
}

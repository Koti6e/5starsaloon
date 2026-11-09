<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\BusinessReportingService;
use App\Support\ReportingPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentReportController extends Controller
{
    public function __invoke(Request $request, BusinessReportingService $reports): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'method' => ['nullable', 'in:cash,upi,card,other'],
        ]);
        $period = ReportingPeriod::fromInput($filters);
        $summary = $reports->summary($period);
        $method = $filters['method'] ?? null;
        $payments = Payment::query()
            ->with(['bill.customer', 'bill.items'])
            ->whereHas('bill', fn ($query) => $query->where('status', 'completed'))
            ->where('paid_at', '>=', $period->start->format('Y-m-d H:i:s'))
            ->where('paid_at', '<', $period->endExclusive->format('Y-m-d H:i:s'))
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->latest('paid_at')
            ->paginate(40)
            ->withQueryString();
        $filteredCollections = $method
            ? $summary['collections']->firstWhere('payment_method', $method)
            : null;

        return view('admin.reports.payments', [
            'period' => $period,
            'summary' => $summary,
            'method' => $method,
            'payments' => $payments,
            'selectedTotal' => $method ? (float) ($filteredCollections->total ?? 0) : $summary['total_collected'],
            'selectedCount' => $method ? (int) ($filteredCollections->count ?? 0) : (int) $summary['collections']->sum('count'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Support\ReportingPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillRegisterController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'q' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'in:cash,upi,card,other'],
            'bill_status' => ['nullable', 'in:all,completed,cancelled,voided'],
        ]);
        $period = ReportingPeriod::fromInput($filters);
        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['bill_status'] ?? 'all';

        $bills = Bill::query()
            ->with(['customer', 'items', 'payments'])
            ->where('billed_at', '>=', $period->start->format('Y-m-d H:i:s'))
            ->where('billed_at', '<', $period->endExclusive->format('Y-m-d H:i:s'))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($filters['payment_method'] ?? null, fn ($query, $method) => $query->whereHas('payments', fn ($payments) => $payments->where('payment_method', $method)))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('invoice_number', 'like', '%'.$search.'%')
                ->orWhereHas('customer', fn ($customer) => $customer
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%'))))
            ->latest('billed_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.billing.index', [
            'period' => $period,
            'bills' => $bills,
            'search' => $search,
            'paymentMethod' => $filters['payment_method'] ?? '',
            'billStatus' => $status,
        ]);
    }
}

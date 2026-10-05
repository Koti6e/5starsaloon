<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? now('Asia/Kolkata')->format('Y-m');
        $monthStart = CarbonImmutable::createFromFormat('!Y-m', $month, 'Asia/Kolkata');
        $nextMonthStart = $monthStart->addMonth();

        $billsForMonth = fn () => Bill::query()
            ->where('bills.status', 'completed')
            ->where('bills.billed_at', '>=', $monthStart->format('Y-m-d H:i:s'))
            ->where('bills.billed_at', '<', $nextMonthStart->format('Y-m-d H:i:s'));

        $summary = $billsForMonth()
            ->selectRaw('COUNT(*) as bill_count, COALESCE(SUM(grand_total), 0) as total_sales')
            ->first();

        $billers = $billsForMonth()
            ->join('users', 'users.id', '=', 'bills.billed_by')
            ->select([
                'users.id as staff_id',
                'users.name as staff_name',
                'users.role as role',
            ])
            ->selectRaw('COUNT(bills.id) as bill_count, COALESCE(SUM(bills.grand_total), 0) as total_sales')
            ->groupBy('users.id', 'users.name', 'users.role')
            ->get()
            ->keyBy('staff_id');

        $activeStaff = User::query()
            ->where('role', 'staff')
            ->where('status', 'active')
            ->get(['id', 'name', 'role']);

        $activeStaffIds = $activeStaff->pluck('id')->flip();
        $staffRows = $activeStaff
            ->map(fn (User $staff) => $billers->get($staff->id) ?? (object) [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'role' => $staff->role,
                'bill_count' => 0,
                'total_sales' => 0,
            ])
            ->concat($billers->reject(fn ($biller) => $activeStaffIds->has($biller->staff_id))->values())
            ->sortBy(fn ($row) => mb_strtolower($row->staff_name))
            ->values()
            ->map(function ($row) use ($summary) {
                $row->sales_percentage = (float) $summary->total_sales > 0
                    ? ((float) $row->total_sales / (float) $summary->total_sales) * 100
                    : 0;

                return $row;
            });

        return view('admin.reports.sales', [
            'month' => $month,
            'monthLabel' => $monthStart->locale(app()->getLocale())->translatedFormat('F Y'),
            'previousMonth' => $monthStart->subMonth()->format('Y-m'),
            'nextMonth' => $nextMonthStart->format('Y-m'),
            'totalSales' => $summary->total_sales,
            'totalBills' => (int) $summary->bill_count,
            'activeStaffCount' => $activeStaff->count(),
            'staffRows' => $staffRows,
            'moneyFormatter' => fn ($amount) => Money::inr($amount),
            'timezone' => 'Asia/Kolkata',
            'salesAttribution' => 'Bills are attributed to the employee in bills.billed_by. Sales use completed bills and bills.grand_total; per-service performers are stored separately on bill items.',
        ]);
    }
}

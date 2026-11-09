<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillItem;
use App\Models\ContactEnquiry;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\BusinessReportingService;
use App\Support\ReportingPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BusinessReportingService $reports): View
    {
        $filters = $this->validatedPeriod($request);
        $period = ReportingPeriod::fromInput($filters);
        $summary = $reports->summary($period);
        $hasAppointmentsTable = Schema::hasTable('appointments');
        if (! $hasAppointmentsTable) {
            Log::warning('Appointments table is unavailable when accessing Admin Dashboard metrics.');
        }
        $appointments = $hasAppointmentsTable
            ? DB::table('appointments')
                ->where('date', '>=', $period->start->toDateString())
                ->where('date', '<', $period->endExclusive->toDateString())
            : null;
        $activeStaff = User::query()->where('role', 'staff')->where('status', 'active')->orderBy('name')->get();
        $attendance = StaffAttendance::query()
            ->whereBetween('attendance_date', [$period->start->toDateString(), $period->endExclusive->subDay()->toDateString()])
            ->get();
        $presentCount = $attendance->whereIn('status', ['present', 'late'])->count();
        $absentCount = $attendance->where('status', 'absent')->count();
        $leaveCount = $attendance->whereIn('status', ['leave', 'weekly_off'])->count();

        if ($period->mode === 'daily') {
            $weeklyOff = $activeStaff->filter(fn (User $staff) => strtolower((string) $staff->weekly_off) === strtolower($period->start->format('l')))->count();
            $absentCount = max(0, $activeStaff->count() - $presentCount - $leaveCount - $weeklyOff);
        }

        $paymentTotals = $summary['collections']->mapWithKeys(fn ($row) => [$row->payment_method => (float) $row->total]);
        $periodBills = $reports->completedBills($period);
        $selectedDayAttendance = $attendance->filter(fn (StaffAttendance $row) => $row->attendance_date->toDateString() === $period->start->toDateString())->keyBy('staff_id');

        return view('admin.dashboard', [
            'period' => $period,
            'summary' => $summary,
            'todaySales' => $summary['net_sales'],
            'todayBills' => $summary['bill_count'],
            'selectedBillDate' => $period->start->toDateString(),
            'selectedBillCount' => $summary['bill_count'],
            'selectedBillSales' => $summary['net_sales'],
            'customersServed' => $reports->customersServed($period),
            'averageBillValue' => $summary['average_bill'],
            'todayAppointments' => $appointments ? (clone $appointments)->count() : 0,
            'staffPresentToday' => $presentCount,
            'staffAbsentToday' => $absentCount,
            'cashSales' => $paymentTotals->get('cash', 0),
            'upiSales' => $paymentTotals->get('upi', 0),
            'cardSales' => $paymentTotals->get('card', 0),
            'otherSales' => (float) $summary['collections']->reject(fn ($row) => in_array($row->payment_method, ['cash', 'upi', 'card'], true))->sum('total'),
            'pendingHomeVisits' => $appointments ? (clone $appointments)->where('appointment_type', 'home_service')->where('status', 'pending')->count() : 0,
            'activeStaff' => $activeStaff->count(),
            'serviceCount' => Service::query()->count(),
            'categoryCount' => ServiceCategory::query()->count(),
            'unreadEnquiries' => ContactEnquiry::query()->where('status', 'unread')->count(),
            'attendanceRows' => $activeStaff->take(8)->map(fn (User $staff) => [
                'staff' => $staff,
                'attendance' => $selectedDayAttendance->get($staff->id),
            ]),
            'todayBillRows' => (clone $periodBills)->with(['customer', 'items', 'payments'])->latest('billed_at')->limit(12)->get(),
            'recentBills' => $reports->completedBills(ReportingPeriod::fromInput([], 'daily'))->with(['customer', 'items', 'payments'])->latest('billed_at')->limit(6)->get(),
            'topServicesToday' => BillItem::query()
                ->select('service_name_snapshot', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(line_total) as total'))
                ->whereHas('bill', fn ($query) => $query
                    ->where('status', 'completed')
                    ->where('billed_at', '>=', $period->start->format('Y-m-d H:i:s'))
                    ->where('billed_at', '<', $period->endExclusive->format('Y-m-d H:i:s')))
                ->groupBy('service_name_snapshot')
                ->orderByDesc('quantity')
                ->limit(5)
                ->get(),
        ]);
    }

    public function liveMetrics(BusinessReportingService $reports): JsonResponse
    {
        $period = ReportingPeriod::fromInput(['period' => 'daily']);
        abort_unless($period->isCurrentBusinessDay(), 404);

        $summary = $reports->summary($period);
        $collections = $summary['collections']->mapWithKeys(fn ($row) => [$row->payment_method => (float) $row->total]);
        $start = $period->start->toDateString();
        $end = $period->endExclusive->toDateString();
        $attendance = StaffAttendance::query()->whereDate('attendance_date', $start)->get();
        $activeStaff = User::query()->where('role', 'staff')->where('status', 'active')->get(['id', 'weekly_off']);
        $present = $attendance->whereIn('status', ['present', 'late'])->count();
        $leave = $attendance->whereIn('status', ['leave', 'weekly_off'])->count();
        $weeklyOff = $activeStaff->filter(fn (User $staff) => strtolower((string) $staff->weekly_off) === strtolower($period->start->format('l')))->count();

        return response()->json([
            'sales' => $summary['net_sales'],
            'bills' => $summary['bill_count'],
            'average_bill' => $summary['average_bill'],
            'customers' => $reports->customersServed($period),
            'cash' => $collections->get('cash', 0),
            'upi' => $collections->get('upi', 0),
            'card' => $collections->get('card', 0),
            'other' => (float) $summary['collections']->reject(fn ($row) => in_array($row->payment_method, ['cash', 'upi', 'card'], true))->sum('total'),
            'pending_home_visits' => DB::table('appointments')->where('date', '>=', $start)->where('date', '<', $end)->where('appointment_type', 'home_service')->where('status', 'pending')->count(),
            'appointments' => DB::table('appointments')->where('date', '>=', $start)->where('date', '<', $end)->count(),
            'staff_present' => $present,
            'staff_absent' => max(0, $activeStaff->count() - $present - $leave - $weeklyOff),
            'updated_at' => now(ReportingPeriod::TIMEZONE)->toIso8601String(),
        ])->header('Cache-Control', 'no-store');
    }

    /** @return array<string, mixed> */
    private function validatedPeriod(Request $request): array
    {
        return $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
    }
}

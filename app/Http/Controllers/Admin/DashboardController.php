<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\ContactEnquiry;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now('Asia/Kolkata')->toDateString();
        $activeStaff = User::query()->where('role', 'staff')->where('status', 'active')->count();
        $attendance = StaffAttendance::query()->whereDate('attendance_date', $today)->get();
        $presentToday = $attendance->whereIn('status', ['present', 'late'])->count();
        $leaveToday = $attendance->whereIn('status', ['leave', 'weekly_off'])->count();

        $hasAppointmentsTable = \Illuminate\Support\Facades\Schema::hasTable('appointments');
        if (! $hasAppointmentsTable) {
            \Illuminate\Support\Facades\Log::warning('Appointments table is unavailable when accessing Admin Dashboard metrics.');
        }

        $todayAppointments = $hasAppointmentsTable
            ? DB::table('appointments')->whereDate('date', $today)->count()
            : 0;

        $pendingHomeVisits = $hasAppointmentsTable
            ? DB::table('appointments')->where('appointment_type', 'home_service')->where('status', 'pending')->count()
            : 0;

        $dayStart = now('Asia/Kolkata')->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();
        $todayBillQuery = Bill::query()->where('status', 'completed')->where('billed_at', '>=', $dayStart)->where('billed_at', '<', $dayEnd);
        $todaySales = (clone $todayBillQuery)->sum('grand_total');
        $todayBillCount = (clone $todayBillQuery)->count();

        return view('admin.dashboard', [
            'todaySales' => $todaySales,
            'todayBills' => $todayBillCount,
            'customersServed' => (clone $todayBillQuery)->distinct('customer_id')->count('customer_id'),
            'averageBillValue' => $todayBillCount > 0 ? $todaySales / $todayBillCount : 0,
            'todayAppointments' => $todayAppointments,
            'staffPresentToday' => $presentToday,
            'staffAbsentToday' => max(0, $activeStaff - $presentToday - $leaveToday),
            'cashSales' => Payment::query()->where('paid_at', '>=', $dayStart)->where('paid_at', '<', $dayEnd)->where('payment_method', 'cash')->whereHas('bill', fn ($query) => $query->where('status', 'completed'))->sum('amount'),
            'upiSales' => Payment::query()->where('paid_at', '>=', $dayStart)->where('paid_at', '<', $dayEnd)->where('payment_method', 'upi')->whereHas('bill', fn ($query) => $query->where('status', 'completed'))->sum('amount'),
            'cardSales' => Payment::query()->where('paid_at', '>=', $dayStart)->where('paid_at', '<', $dayEnd)->where('payment_method', 'card')->whereHas('bill', fn ($query) => $query->where('status', 'completed'))->sum('amount'),
            'pendingHomeVisits' => $pendingHomeVisits,
            'activeStaff' => $activeStaff,
            'serviceCount' => Service::query()->count(),
            'categoryCount' => ServiceCategory::query()->count(),
            'unreadEnquiries' => ContactEnquiry::query()->where('status', 'unread')->count(),
            'attendanceRows' => User::query()
                ->where('role', 'staff')
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (User $staff) => [
                    'staff' => $staff,
                    'attendance' => $attendance->firstWhere('staff_id', $staff->id),
                ]),
            'todayBillRows' => (clone $todayBillQuery)->with(['customer', 'items', 'payments'])->latest('billed_at')->limit(8)->get(),
            'recentBills' => Bill::query()->with(['customer', 'items', 'payments'])
                ->latest('billed_at')->limit(6)->get(),
            'topServicesToday' => BillItem::query()
                ->select('service_name_snapshot', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(line_total) as total'))
                ->whereHas('bill', fn ($query) => $query->whereDate('billed_at', $today))
                ->groupBy('service_name_snapshot')
                ->orderByDesc('quantity')
                ->limit(5)
                ->get(),
        ]);
    }
}

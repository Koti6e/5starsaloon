<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
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

        return view('admin.dashboard', [
            'todaySales' => Bill::query()->whereDate('billed_at', $today)->sum('grand_total'),
            'todayBills' => Bill::query()->whereDate('billed_at', $today)->count(),
            'todayAppointments' => $todayAppointments,
            'staffPresentToday' => $presentToday,
            'staffAbsentToday' => max(0, $activeStaff - $presentToday - $leaveToday),
            'cashSales' => Payment::query()->whereDate('paid_at', $today)->where('payment_method', 'cash')->sum('amount'),
            'upiSales' => Payment::query()->whereDate('paid_at', $today)->where('payment_method', 'upi')->sum('amount'),
            'cardSales' => Payment::query()->whereDate('paid_at', $today)->where('payment_method', 'card')->sum('amount'),
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
        ]);
    }
}

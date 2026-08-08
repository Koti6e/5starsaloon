<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\StaffAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();
        $now = now('Asia/Kolkata');
        $today = $now->toDateString();
        $attendance = StaffAttendance::query()
            ->where('staff_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $hour = (int) $now->format('H');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

        $hasAppointmentsTable = \Illuminate\Support\Facades\Schema::hasTable('appointments');
        if (! $hasAppointmentsTable) {
            \Illuminate\Support\Facades\Log::warning('Appointments table is unavailable when accessing Staff Dashboard metrics.');
        }

        $assignedAppointments = $hasAppointmentsTable
            ? DB::table('appointments')
                ->where('assigned_staff_id', $user->id)
                ->whereDate('date', $today)
                ->count()
            : 0;

        return view('staff.dashboard', [
            'greeting' => $greeting,
            'today' => $now,
            'attendance' => $attendance,
            'todayBills' => Bill::query()->where('billed_by', $user->id)->whereDate('billed_at', $today)->count(),
            'todaySales' => Bill::query()->where('billed_by', $user->id)->whereDate('billed_at', $today)->sum('grand_total'),
            'assignedAppointments' => $assignedAppointments,
            'recentBills' => Bill::query()
                ->with('customer')
                ->where('billed_by', $user->id)
                ->latest('billed_at')
                ->limit(5)
                ->get(),
        ]);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\StaffAttendance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffSessionBeforeDailyCutoff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->isStaff()) {
            return $next($request);
        }

        $now = now('Asia/Kolkata');
        $loginDate = $request->session()->get('staff_login_date', $now->toDateString());
        $cutoff = $now->copy()->setTime(21, 30);

        if ($loginDate !== $now->toDateString() || $now->greaterThanOrEqualTo($cutoff)) {
            StaffAttendance::query()
                ->where('staff_id', $user->id)
                ->whereDate('attendance_date', $loginDate)
                ->whereNull('check_out_time')
                ->whereNot('source', 'manual_admin')
                ->update(['check_out_time' => $cutoff->format('H:i:s')]);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Your staff session ended at the daily 9:30 PM cutoff. Please login again.');
        }

        return $next($request);
    }
}

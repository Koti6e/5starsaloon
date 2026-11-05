<?php

namespace App\Http\Middleware;

use App\Models\StaffAttendance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffLoginSelfieCaptured
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->requiresLoginSelfie()) {
            $now = now('Asia/Kolkata');
            $attendance = StaffAttendance::query()
                ->where('staff_id', $user->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->whereNotNull('selfie_captured_at')
                ->first();

            if ($attendance) {
                $request->session()->put('staff_selfie_attendance_id', $attendance->id);
            } elseif (! $request->routeIs('staff.selfie.*')) {
                return redirect()->route('staff.selfie.create');
            }
        }

        return $next($request);
    }
}

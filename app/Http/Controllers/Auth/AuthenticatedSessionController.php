<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\StaffAttendance;
use App\Services\AttendanceGeofence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, AttendanceGeofence $geofence): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put('staff_login_date', now('Asia/Kolkata')->toDateString());

        if ($request->user()->must_change_password) {
            return redirect()->route('password.force.edit');
        }

        if ($request->user()->isStaff() && ! $request->user()->requiresLoginSelfie()) {
            try {
                $location = $geofence->verify($request->input('latitude'), $request->input('longitude'), $request->input('accuracy'));
            } catch (\Illuminate\Validation\ValidationException $exception) {
                Auth::logout();
                throw $exception;
            }
        }

        if ($request->user()->requiresLoginSelfie()) {
            $now = now('Asia/Kolkata');
            $weeklyOff = mb_strtolower((string) $request->user()->weekly_off) === mb_strtolower($now->format('l'));
            $verifiedToday = null;
            if (! $weeklyOff) {
                $verifiedToday = StaffAttendance::query()
                    ->where('staff_id', $request->user()->id)
                    ->whereDate('attendance_date', $now->toDateString())
                    ->whereNotNull('selfie_captured_at')
                    ->first();
            }

            if ($verifiedToday) {
                $request->session()->put('staff_selfie_attendance_id', $verifiedToday->id);
            } elseif (! $weeklyOff) {
                return redirect()->intended(route('staff.selfie.create', absolute: false));
            }
        }

        if ($request->user()->isStaff() && ! $request->user()->requiresLoginSelfie()) {
            StaffAttendance::query()->firstOrCreate(
                [
                    'staff_id' => $request->user()->id,
                    'attendance_date' => now('Asia/Kolkata')->toDateString(),
                ],
                [
                    'status' => 'present',
                    'check_in_time' => now('Asia/Kolkata')->format('H:i:s'),
                    'source' => 'automatic_login',
                    'notes' => 'Auto-marked from staff login.',
                    'check_in_latitude' => $location['latitude'], 'check_in_longitude' => $location['longitude'], 'check_in_gps_accuracy' => $location['accuracy'], 'check_in_distance_from_salon' => $location['distance'], 'check_in_gps_verification_status' => $location['status'], 'check_in_location_captured_at' => now('Asia/Kolkata'),
                ],
            );
        }

        return redirect()->intended(route($request->user()->isAdmin() ? 'admin.dashboard' : 'staff.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, AttendanceGeofence $geofence): RedirectResponse
    {
        $user = $request->user();

        if ($user?->isStaff()) {
            $location = $geofence->verify($request->input('latitude'), $request->input('longitude'), $request->input('accuracy'));
            $logoutUpdates = [
                'check_out_time' => now('Asia/Kolkata')->format('H:i:s'),
                'check_out_latitude' => $location['latitude'], 'check_out_longitude' => $location['longitude'], 'check_out_gps_accuracy' => $location['accuracy'], 'check_out_distance_from_salon' => $location['distance'], 'check_out_gps_verification_status' => $location['status'], 'check_out_location_captured_at' => now('Asia/Kolkata'),
            ];

            if (! $user->requiresLoginSelfie()) {
                $logoutUpdates['source'] = 'automatic_logout';
            }

            StaffAttendance::query()
                ->where('staff_id', $user->id)
                ->whereDate('attendance_date', now('Asia/Kolkata')->toDateString())
                ->whereNull('check_out_time')
                ->whereNot('source', 'manual_admin')
                ->update($logoutUpdates);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

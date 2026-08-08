<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\StaffAttendance;
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
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()->must_change_password) {
            return redirect()->route('password.force.edit');
        }

        if ($request->user()->isStaff()) {
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
                ],
            );
        }

        return redirect()->intended(route($request->user()->isAdmin() ? 'admin.dashboard' : 'staff.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->isStaff()) {
            StaffAttendance::query()
                ->where('staff_id', $user->id)
                ->whereDate('attendance_date', now('Asia/Kolkata')->toDateString())
                ->whereNull('check_out_time')
                ->whereNot('source', 'manual_admin')
                ->update([
                    'check_out_time' => now('Asia/Kolkata')->format('H:i:s'),
                    'source' => 'automatic_logout',
                ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

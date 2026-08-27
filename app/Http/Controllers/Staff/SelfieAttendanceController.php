<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SelfieAttendanceController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->requiresLoginSelfie()) {
            return redirect()->route('staff.dashboard');
        }

        if ($request->session()->has('staff_selfie_attendance_id')) {
            return redirect()->intended(route('staff.dashboard', absolute: false));
        }

        return view('staff.selfie');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->requiresLoginSelfie(), 404);

        $validated = $request->validate([
            'selfie_image' => ['required', 'string'],
        ]);

        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $validated['selfie_image'], $matches)) {
            throw ValidationException::withMessages(['selfie_image' => 'Capture a fresh selfie using the camera and try again.']);
        }

        $binary = base64_decode(substr($validated['selfie_image'], strpos($validated['selfie_image'], ',') + 1), true);
        if ($binary === false || strlen($binary) < 10_000 || strlen($binary) > 4_500_000) {
            throw ValidationException::withMessages(['selfie_image' => 'Selfie upload failed. Please retake the photo.']);
        }

        $extension = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $now = now('Asia/Kolkata');
        $sessionId = $request->session()->getId();
        $path = 'staff-selfies/'.$now->format('Y/m/d').'/staff-'.$request->user()->id.'-'.Str::uuid().'.'.$extension;

        Storage::disk('local')->put($path, $binary);

        $attendance = StaffAttendance::query()->updateOrCreate(
            [
                'staff_id' => $request->user()->id,
                'attendance_date' => $now->toDateString(),
            ],
            [
                'status' => 'present',
                'check_in_time' => $now->format('H:i:s'),
                'source' => 'selfie_login',
                'login_session_id' => $sessionId,
                'selfie_path' => $path,
                'selfie_captured_at' => $now,
                'notes' => 'Selfie verified at login.',
            ],
        );

        $request->session()->put('staff_selfie_attendance_id', $attendance->id);

        return redirect()->intended(route('staff.dashboard', absolute: false));
    }
}

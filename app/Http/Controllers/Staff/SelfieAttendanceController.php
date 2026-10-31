<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\AttendanceVerificationAttempt;
use App\Models\StaffAttendanceDevice;
use App\Services\AttendanceGeofence;
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

        $now = now('Asia/Kolkata');
        if (mb_strtolower((string) $request->user()->weekly_off) === mb_strtolower($now->format('l'))) {
            return redirect()->route('staff.dashboard');
        }

        $verifiedToday = StaffAttendance::query()
            ->where('staff_id', $request->user()->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->whereNotNull('selfie_captured_at')
            ->first();
        if ($verifiedToday) {
            $request->session()->put('staff_selfie_attendance_id', $verifiedToday->id);
            return redirect()->route('staff.dashboard', absolute: false);
        }

        if ($request->session()->has('staff_selfie_attendance_id')) {
            return redirect()->route('staff.dashboard', absolute: false);
        }

        return view('staff.selfie');
    }

    public function store(Request $request, AttendanceGeofence $geofence): RedirectResponse
    {
        abort_unless($request->user()->requiresLoginSelfie(), 404);

        $today = now('Asia/Kolkata');
        if (mb_strtolower((string) $request->user()->weekly_off) === mb_strtolower($today->format('l'))) {
            return redirect()->route('staff.dashboard');
        }

        $validated = $request->validate([
            'selfie_image' => ['nullable', 'string', 'max:6000000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'device_key' => ['nullable', 'uuid'],
            'consent' => ['accepted'],
        ]);

        $now = $today;
        $latitude = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $longitude = isset($validated['longitude']) ? (float) $validated['longitude'] : null;
        $accuracy = isset($validated['gps_accuracy_meters']) ? (float) $validated['gps_accuracy_meters'] : null;
        $location = $geofence->evaluate($latitude, $longitude, $accuracy);
        $deviceHash = filled($validated['device_key'] ?? null) ? hash('sha256', $validated['device_key']) : null;
        $device = $deviceHash
            ? StaffAttendanceDevice::query()->firstOrCreate(
                ['staff_id' => $request->user()->id, 'device_key_hash' => $deviceHash],
                ['status' => 'pending', 'first_seen_at' => $now, 'last_seen_at' => $now, 'label' => Str::limit((string) $request->userAgent(), 100)],
            )
            : null;
        if ($device) {
            $device->forceFill(['last_seen_at' => $now])->save();
        }

        $logRejectedAttempt = function (string $reason) use ($request, $now, $device, $latitude, $longitude, $accuracy, $location): RedirectResponse {
            AttendanceVerificationAttempt::query()->create([
                'staff_id' => $request->user()->id,
                'device_id' => $device?->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'gps_accuracy_meters' => $accuracy,
                'distance_from_salon_meters' => is_finite($location['distance']) ? $location['distance'] : null,
                'decision' => 'rejected',
                'reason' => $reason,
                'attempted_at' => $now,
            ]);

            return back()->withErrors(['attendance' => $reason]);
        };

        if (! $location['allowed']) {
            return $logRejectedAttempt($location['reason'] ?? 'GPS verification failed.');
        }

        if ($device?->status === 'revoked') {
            return $logRejectedAttempt('This device has been revoked. Ask an administrator to review the device change.');
        }

        if (blank($validated['device_key'] ?? null)) {
            return $logRejectedAttempt('Device registration is required. Reload the page and try again.');
        }

        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', (string) ($validated['selfie_image'] ?? ''), $matches)) {
            return $logRejectedAttempt('Capture a fresh selfie using the camera and try again.');
        }

        $binary = base64_decode(substr($validated['selfie_image'], strpos($validated['selfie_image'], ',') + 1), true);
        if ($binary === false || strlen($binary) < 10_000 || strlen($binary) > 4_500_000) {
            return $logRejectedAttempt('Selfie upload failed. Please retake the photo.');
        }

        $extension = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $existingAttendance = StaffAttendance::query()
            ->where('staff_id', $request->user()->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();
        $sessionId = $request->session()->getId();
        $path = 'staff-selfies/'.$now->format('Y/m/d').'/staff-'.$request->user()->id.'-'.Str::uuid().'.'.$extension;

        Storage::disk('local')->put($path, $binary);

        $reviewReason = $device->status !== 'approved'
            ? 'New device registration is pending administrator review. '
            : '';
        $reviewReason .= 'Face matching and liveness verification are not configured; a camera capture alone does not prove identity or prevent replay.';

        if ($existingAttendance?->source === 'manual_admin') {
            $attendance = $existingAttendance;
            $attendance->forceFill([
                'login_session_id' => $sessionId,
                'selfie_path' => $path,
                'selfie_captured_at' => $now,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'gps_accuracy_meters' => $accuracy,
                'distance_from_salon_meters' => $location['distance'],
                'device_id' => $device->id,
                'verification_status' => 'review_required',
                'verification_reason' => $reviewReason,
                'face_verification_status' => 'unavailable',
                'consent_at' => $now,
            ])->save();
        } else {
            $attendance = StaffAttendance::query()->updateOrCreate(
                ['staff_id' => $request->user()->id, 'attendance_date' => $now->toDateString()],
                [
                    'status' => 'present',
                    'check_in_time' => $existingAttendance?->check_in_time ?: $now->format('H:i:s'),
                    'source' => 'selfie_login',
                    'login_session_id' => $sessionId,
                    'selfie_path' => $path,
                    'selfie_captured_at' => $now,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'gps_accuracy_meters' => $accuracy,
                    'distance_from_salon_meters' => $location['distance'],
                    'device_id' => $device->id,
                    'verification_status' => 'review_required',
                    'verification_reason' => $reviewReason,
                    'face_verification_status' => 'unavailable',
                    'consent_at' => $now,
                    'notes' => 'GPS geofence passed; selfie is awaiting human review because no face/liveness service is configured.',
                ],
            );
        }

        AttendanceVerificationAttempt::query()->create([
            'staff_id' => $request->user()->id,
            'attendance_id' => $attendance->id,
            'device_id' => $device->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'gps_accuracy_meters' => $accuracy,
            'distance_from_salon_meters' => $location['distance'],
            'decision' => 'review_required',
            'reason' => $reviewReason,
            'selfie_path' => $path,
            'attempted_at' => $now,
        ]);

        $request->session()->put('staff_selfie_attendance_id', $attendance->id);

        return redirect()->intended(route('staff.dashboard', absolute: false));
    }
}

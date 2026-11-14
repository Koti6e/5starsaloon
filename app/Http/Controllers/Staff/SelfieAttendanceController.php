<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceVerificationAttempt;
use App\Models\StaffAttendance;
use App\Models\StaffAttendanceDevice;
use App\Models\User;
use App\Services\AttendanceGeofence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SelfieAttendanceController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->requiresLoginSelfie()) {
            return redirect()->route('staff.dashboard');
        }

        $now = now('Asia/Kolkata');
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

        return view('staff.selfie', [
            'requiresGps' => $request->user()->requiresLoginGeofence(),
        ]);
    }

    public function store(Request $request, AttendanceGeofence $geofence): RedirectResponse
    {
        abort_unless($request->user()->requiresLoginSelfie(), 404);

        $today = now('Asia/Kolkata');
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
        $requiresGps = $request->user()->requiresLoginGeofence();
        $location = $requiresGps
            ? $geofence->evaluate($latitude, $longitude, $accuracy)
            : ['distance' => INF, 'radius' => 200.0, 'accuracy_limit' => null, 'allowed' => true, 'reason' => null];
        $distance = is_finite($location['distance']) ? $location['distance'] : null;
        $logRejectedAttempt = function (string $reason, ?StaffAttendanceDevice $device = null) use ($request, $now, $latitude, $longitude, $accuracy, $distance): RedirectResponse {
            AttendanceVerificationAttempt::query()->create([
                'staff_id' => $request->user()->id,
                'device_id' => $device?->id,
                'attempt_type' => 'attendance_selfie',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'gps_accuracy_meters' => $accuracy,
                'distance_from_salon_meters' => $distance,
                'decision' => 'rejected',
                'reason' => $reason,
                'attempted_at' => $now,
            ]);

            return back()->withErrors(['attendance' => $reason]);
        };

        if (! $location['allowed']) {
            return $logRejectedAttempt($location['reason'] ?? 'GPS verification failed.');
        }

        if (blank($validated['device_key'] ?? null)) {
            return $logRejectedAttempt('Device registration is required. Reload the page and try again.');
        }

        [$binary, $extension] = $this->decodeSelfie((string) ($validated['selfie_image'] ?? ''));
        if ($binary === null) {
            return $logRejectedAttempt('The selfie is not a valid JPEG, PNG or WebP image. Retake it and try again.');
        }

        $newSelfiePath = null;
        $writingSelfie = false;

        try {
            $attendance = DB::transaction(function () use ($request, $now, $latitude, $longitude, $accuracy, $distance, $requiresGps, $binary, $extension, &$newSelfiePath, &$writingSelfie): StaffAttendance {
                // Serialize all submissions for one staff member. The table also has a unique
                // staff/date key, so a retry cannot create a second daily attendance row.
                $staff = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $attendance = StaffAttendance::query()
                    ->where('staff_id', $staff->id)
                    ->whereDate('attendance_date', $now->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($attendance && ! in_array($attendance->status, ['present', 'half_day', 'weekly_off'], true)) {
                    throw new AttendanceSubmissionRejected('Today’s attendance is already marked '.$attendance->status.'. It was not changed. Ask an administrator to review it.');
                }

                if ($attendance?->selfie_captured_at && filled($attendance->selfie_path)) {
                    return $attendance;
                }

                $deviceHash = hash('sha256', $request->string('device_key')->toString());
                $deviceLabel = mb_substr(trim((string) $request->userAgent()), 0, 100);
                $device = StaffAttendanceDevice::query()->firstOrCreate(
                    ['staff_id' => $staff->id, 'device_key_hash' => $deviceHash],
                    ['status' => 'pending', 'first_seen_at' => $now, 'last_seen_at' => $now, 'label' => $deviceLabel],
                );
                $device->forceFill(['last_seen_at' => $now])->save();

                if ($device->status === 'revoked') {
                    throw new AttendanceSubmissionRejected('This device has been revoked. Ask an administrator to review it.', $device->id);
                }

                $newSelfiePath = 'staff-selfies/'.$now->format('Y/m/d').'/staff-'.$staff->id.'-'.Str::uuid().'.'.$extension;
                $writingSelfie = true;
                if (! Storage::disk('local')->put($newSelfiePath, $binary)) {
                    throw new RuntimeException('Selfie evidence could not be stored.');
                }
                $writingSelfie = false;

                $reviewReason = $device->status !== 'approved'
                    ? 'New device registration is pending administrator review. '
                    : '';
                $reviewReason .= $requiresGps
                    ? 'GPS geofence passed; '
                    : 'GPS geofencing does not apply to this staff account; ';
                $reviewReason .= 'face matching and liveness verification are not configured, so a camera capture alone does not prove identity or prevent replay.';
                $verificationNote = $requiresGps
                    ? 'GPS geofence passed; selfie is awaiting human review because no face/liveness service is configured.'
                    : 'Selfie is awaiting human review because no face/liveness service is configured.';

                $evidence = [
                    'login_session_id' => $request->session()->getId(),
                    'selfie_path' => $newSelfiePath,
                    'selfie_captured_at' => $now,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'gps_accuracy_meters' => $accuracy,
                    'distance_from_salon_meters' => $distance,
                    'device_id' => $device->id,
                    'verification_status' => 'review_required',
                    'verification_reason' => $reviewReason,
                    'face_verification_status' => 'unavailable',
                    'consent_at' => $now,
                ];

                if ($attendance) {
                    // Attach today's evidence without changing an administrator's or another
                    // process's existing attendance status, check-in time, or source.
                    $attendance->forceFill($evidence)->save();
                } else {
                    $isWeeklyOff = mb_strtolower((string) $staff->weekly_off) === mb_strtolower($now->format('l'));
                    $attendance = StaffAttendance::query()->create($evidence + [
                        'staff_id' => $staff->id,
                        'attendance_date' => $now->toDateString(),
                        'status' => $isWeeklyOff ? 'weekly_off' : 'present',
                        'check_in_time' => $isWeeklyOff ? null : $now->format('H:i:s'),
                        'source' => 'selfie_login',
                        'notes' => $verificationNote,
                    ]);
                }

                AttendanceVerificationAttempt::query()->create([
                    'staff_id' => $staff->id,
                    'attendance_id' => $attendance->id,
                    'device_id' => $device->id,
                    'attempt_type' => 'attendance_selfie',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'gps_accuracy_meters' => $accuracy,
                    'distance_from_salon_meters' => $distance,
                    'decision' => 'review_required',
                    'reason' => $reviewReason,
                    'selfie_path' => $newSelfiePath,
                    'attempted_at' => $now,
                ]);

                return $attendance;
            });
        } catch (AttendanceSubmissionRejected $exception) {
            $device = $exception->deviceId ? StaffAttendanceDevice::query()->find($exception->deviceId) : null;

            return $logRejectedAttempt($exception->getMessage(), $device);
        } catch (Throwable $exception) {
            if ($newSelfiePath !== null) {
                try {
                    $isReferenced = DB::table('staff_attendances')->where('selfie_path', $newSelfiePath)->exists()
                        || DB::table('attendance_verification_attempts')->where('selfie_path', $newSelfiePath)->exists();
                    if (! $isReferenced) {
                        Storage::disk('local')->delete($newSelfiePath);
                    }
                } catch (Throwable) {
                    Log::warning('Could not clean up a newly stored attendance selfie after a failed save.', [
                        'staff_id' => $request->user()->id,
                    ]);
                }
            }

            report($exception);

            return back()->withErrors([
                'attendance' => $writingSelfie
                    ? 'The selfie could not be stored. Attendance was not marked. Retake the photo and try again; contact an administrator if the problem continues.'
                    : 'Attendance could not be saved. No check-in was recorded. Check your connection and try again; contact an administrator if the problem continues.',
            ]);
        }

        $request->session()->put('staff_selfie_attendance_id', $attendance->id);

        return redirect()->to(route('staff.dashboard', ['attendance_checkin' => 'saved'], false))
            ->with('status', 'Attendance was verified and saved.');
    }

    /** @return array{0: ?string, 1: ?string} */
    private function decodeSelfie(string $dataUrl): array
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            return [null, null];
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || strlen($binary) < 1_000 || strlen($binary) > 4_500_000) {
            return [null, null];
        }

        $image = @getimagesizefromstring($binary);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        $declaredMime = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $mimeType = 'image/'.$declaredMime;
        if (! $image || $mime !== $mimeType || $image['mime'] !== $mimeType
            || $image[0] < 1 || $image[1] < 1 || $image[0] > 4096 || $image[1] > 4096
            || ($image[0] * $image[1]) > 12_000_000) {
            return [null, null];
        }

        $decodedImage = @imagecreatefromstring($binary);
        if ($decodedImage === false) {
            return [null, null];
        }
        imagedestroy($decodedImage);

        return [$binary, $declaredMime];
    }
}

class AttendanceSubmissionRejected extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $deviceId = null)
    {
        parent::__construct($message);
    }
}

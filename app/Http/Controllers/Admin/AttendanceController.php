<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceVerificationAttempt;
use App\Models\StaffAttendance;
use App\Models\StaffAttendanceDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['date' => ['nullable', 'date_format:Y-m-d'], 'month' => ['nullable', 'date_format:Y-m'], 'staff' => ['nullable', 'integer']]);
        $date = $filters['date'] ?? today('Asia/Kolkata')->toDateString();
        $month = Carbon::createFromFormat('Y-m', $filters['month'] ?? Carbon::parse($date)->format('Y-m'), 'Asia/Kolkata')->startOfMonth();
        $staff = User::query()
            ->where('role', 'staff')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $attendance = StaffAttendance::query()
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('staff_id');

        $weekStart = Carbon::parse($date, 'Asia/Kolkata')->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $weeklyRecords = StaffAttendance::query()
            ->whereBetween('attendance_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy('staff_id');
        $weeklySummary = $staff->mapWithKeys(function (User $member) use ($weeklyRecords, $weekStart): array {
            $records = $weeklyRecords->get($member->id, collect())->keyBy(fn (StaffAttendance $row) => $row->attendance_date->format('Y-m-d'));
            $weeklyOff = strtolower((string) $member->weekly_off);
            $workingDays = 0;
            $present = $absent = $leave = $halfDay = 0;
            $days = collect();

            for ($offset = 0; $offset < 7; $offset++) {
                $day = $weekStart->copy()->addDays($offset);
                $row = $records->get($day->toDateString());
                $isWeeklyOff = $weeklyOff !== '' && strtolower($day->format('l')) === $weeklyOff;
                $status = $row?->status ?? ($isWeeklyOff ? 'weekly_off' : 'not_marked');
                if (! $isWeeklyOff) {
                    $workingDays++;
                }
                if (in_array($status, ['present', 'late'], true)) {
                    $present++;
                } elseif ($status === 'absent') {
                    $absent++;
                } elseif ($status === 'leave') {
                    $leave++;
                } elseif ($status === 'half_day') {
                    $halfDay++;
                }
                $days->push(['date' => $day->toDateString(), 'status' => $status, 'row' => $row]);
            }

            return [$member->id => [
                'working_days' => $workingDays,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'half_day' => $halfDay,
                'attendance' => $workingDays ? round((($present + $halfDay * 0.5) / $workingDays) * 100, 1) : 0,
                'days' => $days,
            ]];
        });

        $monthlyRecords = StaffAttendance::query()
            ->whereBetween('attendance_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->get()
            ->groupBy('staff_id');
        $daysInScope = $month->isSameMonth(now('Asia/Kolkata')) ? now('Asia/Kolkata')->day : $month->daysInMonth;
        $monthlySummary = $staff->mapWithKeys(function (User $member) use ($monthlyRecords, $month, $daysInScope): array {
            $records = $monthlyRecords->get($member->id, collect())->keyBy(fn (StaffAttendance $row) => $row->attendance_date->format('Y-m-d'));
            $weeklyOff = strtolower((string) $member->weekly_off);
            $workingDays = collect(range(1, $daysInScope))->map(fn (int $day) => $month->copy()->day($day))
                ->reject(fn (Carbon $day) => $weeklyOff !== '' && strtolower($day->format('l')) === $weeklyOff)->count();
            $counts = ['present' => 0, 'absent' => 0, 'leave' => 0, 'half_day' => 0];
            $daily = collect();
            foreach (range(1, $daysInScope) as $dayNumber) {
                $day = $month->copy()->day($dayNumber);
                $row = $records->get($day->toDateString());
                $status = $row?->status ?? (($weeklyOff !== '' && strtolower($day->format('l')) === $weeklyOff) ? 'weekly_off' : 'not_marked');
                if (array_key_exists($status, $counts)) {
                    $counts[$status]++;
                }
                if ($status === 'late') {
                    $counts['present']++;
                }
                $daily->push(['date' => $day->toDateString(), 'status' => $status, 'row' => $row]);
            }

            return [$member->id => [...$counts, 'working_days' => $workingDays, 'attendance' => $workingDays ? round((($counts['present'] + $counts['half_day'] * 0.5) / $workingDays) * 100, 1) : 0, 'daily' => $daily]];
        });

        return view('admin.attendance.index', [
            'date' => $date,
            'staff' => $staff,
            'attendance' => $attendance,
            'month' => $month,
            'monthlySummary' => $monthlySummary,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weeklySummary' => $weeklySummary,
            'verificationAttempts' => AttendanceVerificationAttempt::query()->with('staff')->where('attempt_type', 'attendance_selfie')->whereNull('reviewed_at')->latest('attempted_at')->limit(30)->get(),
            'loginAttempts' => AttendanceVerificationAttempt::query()
                ->with('staff')
                ->where('attempt_type', 'login_gps')
                ->latest('attempted_at')
                ->paginate(50, ['*'], 'login_page')
                ->withQueryString(),
            'attendanceDevices' => StaffAttendanceDevice::query()->with('staff')->whereIn('status', ['pending', 'approved'])->latest('last_seen_at')->limit(50)->get(),
            'selectedStaff' => (int) ($filters['staff'] ?? 0),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'staff_id' => ['required', 'exists:users,id'],
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'leave', 'half_day', 'weekly_off', 'not_marked'])],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'correction_reason' => ['required', 'string', 'max:1000'],
            'verification_status' => ['nullable', Rule::in(['review_required', 'verified', 'rejected'])],
        ]);

        $staff = User::query()
            ->whereKey($validated['staff_id'])
            ->where('role', 'staff')
            ->where('status', 'active')
            ->firstOrFail();

        $attendance = StaffAttendance::query()->firstOrNew(
            ['staff_id' => $staff->id, 'attendance_date' => $validated['attendance_date']],
        );
        $previousStatus = $attendance->status;
        $previousVerification = $attendance->verification_status;
        $newVerification = $validated['verification_status'] ?? $previousVerification ?? 'review_required';
        $attendance->fill([
            'status' => $validated['status'],
            'check_in_time' => $validated['check_in_time'] ?? null,
            'check_out_time' => $validated['check_out_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'marked_by' => $request->user()->id,
            'source' => 'manual_admin',
            'corrected_by' => $request->user()->id,
            'correction_reason' => $validated['correction_reason'],
            'corrected_at' => now(),
            'verification_status' => $newVerification,
            'verification_reason' => $validated['correction_reason'],
        ])->save();

        DB::table('staff_attendance_audits')->insert([
            'attendance_id' => $attendance->id,
            'staff_id' => $staff->id,
            'admin_id' => $request->user()->id,
            'action' => $newVerification === 'verified' ? 'admin_approved' : 'manual_correction',
            'previous_status' => $previousStatus,
            'new_status' => $attendance->status,
            'previous_verification_status' => $previousVerification,
            'new_verification_status' => $newVerification,
            'reason' => $validated['correction_reason'],
            'created_at' => now(),
        ]);

        return back()->with('status', 'Attendance updated.');
    }

    public function reviewAttempt(Request $request, AttendanceVerificationAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->attempt_type === 'attendance_selfie', 404);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_reason' => ['required', 'string', 'max:1000'],
        ]);

        abort_if($attempt->reviewed_at, 409, 'This attendance attempt has already been reviewed.');
        $now = now();

        DB::transaction(function () use ($request, $attempt, $validated, $now): void {
            $locked = AttendanceVerificationAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            abort_if($locked->reviewed_at, 409, 'This attendance attempt has already been reviewed.');
            $attendanceId = null;

            if ($validated['decision'] === 'approved') {
                $staff = User::query()->whereKey($locked->staff_id)->where('role', 'staff')->firstOrFail();
                $day = $locked->attempted_at->timezone('Asia/Kolkata')->toDateString();
                $attendance = StaffAttendance::query()->firstOrNew(['staff_id' => $staff->id, 'attendance_date' => $day]);
                $previousStatus = $attendance->status;
                $previousVerification = $attendance->verification_status;
                $attendance->fill([
                    'status' => 'present',
                    'check_in_time' => $attendance->check_in_time ?: $locked->attempted_at->timezone('Asia/Kolkata')->format('H:i:s'),
                    'source' => 'admin_override',
                    'marked_by' => $request->user()->id,
                    'corrected_by' => $request->user()->id,
                    'correction_reason' => $validated['review_reason'],
                    'corrected_at' => $now,
                    'latitude' => $locked->latitude,
                    'longitude' => $locked->longitude,
                    'gps_accuracy_meters' => $locked->gps_accuracy_meters,
                    'distance_from_salon_meters' => $locked->distance_from_salon_meters,
                    'device_id' => $locked->device_id,
                    'selfie_path' => $locked->selfie_path,
                    'selfie_captured_at' => $locked->attempted_at,
                    'consent_at' => $locked->selfie_path ? $locked->attempted_at : null,
                    'verification_status' => 'verified',
                    'verification_reason' => 'Administrator exception approval: '.$validated['review_reason'],
                    'face_verification_status' => 'unavailable',
                ])->save();
                $attendanceId = $attendance->id;

                DB::table('staff_attendance_audits')->insert([
                    'attendance_id' => $attendance->id,
                    'staff_id' => $staff->id,
                    'admin_id' => $request->user()->id,
                    'action' => 'verification_override',
                    'previous_status' => $previousStatus,
                    'new_status' => 'present',
                    'previous_verification_status' => $previousVerification,
                    'new_verification_status' => 'verified',
                    'reason' => $validated['review_reason'],
                    'created_at' => $now,
                ]);
            }

            $locked->forceFill([
                'attendance_id' => $attendanceId,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => $now,
                'review_decision' => $validated['decision'],
                'review_reason' => $validated['review_reason'],
            ])->save();
        });

        return back()->with('status', 'Attendance verification review recorded.');
    }

    public function updateDevice(Request $request, StaffAttendanceDevice $device): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'revoke'])],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $now = now();
        $status = $validated['action'] === 'approve' ? 'approved' : 'revoked';
        $previousStatus = $device->status;
        $device->forceFill([
            'status' => $status,
            'approved_by' => $status === 'approved' ? $request->user()->id : null,
            'approved_at' => $status === 'approved' ? $now : null,
            'revoked_by' => $status === 'revoked' ? $request->user()->id : null,
            'revoked_at' => $status === 'revoked' ? $now : null,
        ])->save();

        DB::table('staff_attendance_device_events')->insert([
            'device_id' => $device->id,
            'admin_id' => $request->user()->id,
            'previous_status' => $previousStatus,
            'action' => $status,
            'reason' => $validated['reason'],
            'created_at' => $now,
        ]);

        return back()->with('status', 'Staff attendance device '.$status.'.');
    }
}

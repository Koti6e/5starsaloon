<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
                if (array_key_exists($status, $counts)) $counts[$status]++;
                if ($status === 'late') $counts['present']++;
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
        ]);

        $staff = User::query()
            ->whereKey($validated['staff_id'])
            ->where('role', 'staff')
            ->where('status', 'active')
            ->firstOrFail();

        StaffAttendance::query()->updateOrCreate(
            ['staff_id' => $staff->id, 'attendance_date' => $validated['attendance_date']],
            [
                'status' => $validated['status'],
                'check_in_time' => $validated['check_in_time'] ?? null,
                'check_out_time' => $validated['check_out_time'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'marked_by' => $request->user()->id,
                'source' => 'manual_admin',
                'corrected_by' => $request->user()->id,
                'correction_reason' => $validated['correction_reason'],
                'corrected_at' => now(),
            ],
        );

        return back()->with('status', 'Attendance updated.');
    }
}

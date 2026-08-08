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
        $date = Carbon::parse($request->input('date', today()->toDateString()))->toDateString();
        $staff = User::query()
            ->where('role', 'staff')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $attendance = StaffAttendance::query()
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('staff_id');

        return view('admin.attendance.index', [
            'date' => $date,
            'staff' => $staff,
            'attendance' => $attendance,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'staff_id' => ['required', 'exists:users,id'],
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'leave', 'weekly_off', 'not_marked'])],
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

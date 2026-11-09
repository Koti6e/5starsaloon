<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Support\ReportingPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function show(Request $request, User $staff): View
    {
        abort_unless($staff->isStaff(), 404);

        $filters = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $period = ReportingPeriod::fromInput($filters, 'monthly');

        $billSummary = Bill::query()
            ->where('bills.billed_by', $staff->id)
            ->where('bills.status', 'completed')
            ->where('bills.billed_at', '>=', $period->start->format('Y-m-d H:i:s'))
            ->where('bills.billed_at', '<', $period->endExclusive->format('Y-m-d H:i:s'))
            ->selectRaw('COUNT(*) as bill_count, COALESCE(SUM(bills.grand_total), 0) as total_sales, COALESCE(AVG(bills.grand_total), 0) as average_bill')
            ->first();

        $serviceRows = BillItem::query()
            ->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->where('bill_items.service_performed_by', $staff->id)
            ->where('bills.status', 'completed')
            ->where('bills.billed_at', '>=', $period->start->format('Y-m-d H:i:s'))
            ->where('bills.billed_at', '<', $period->endExclusive->format('Y-m-d H:i:s'))
            ->select('bill_items.service_name_snapshot')
            ->selectRaw('SUM(bill_items.quantity) as quantity, SUM(bill_items.line_total) as line_total_before_bill_discount')
            ->groupBy('bill_items.service_name_snapshot')
            ->orderByDesc('line_total_before_bill_discount')
            ->get();

        $attendanceRows = StaffAttendance::query()
            ->where('staff_id', $staff->id)
            ->where('attendance_date', '>=', $period->start->toDateString())
            ->where('attendance_date', '<', $period->endExclusive->toDateString())
            ->orderByDesc('attendance_date')
            ->get()
            ->map(function (StaffAttendance $row): StaffAttendance {
                $row->setAttribute('worked_minutes', null);
                if ($row->check_in_time && $row->check_out_time) {
                    $start = SupportCarbon::parse($row->attendance_date->toDateString().' '.$row->check_in_time, ReportingPeriod::TIMEZONE);
                    $end = SupportCarbon::parse($row->attendance_date->toDateString().' '.$row->check_out_time, ReportingPeriod::TIMEZONE);
                    if ($end->lessThanOrEqualTo($start)) {
                        $end->addDay();
                    }
                    $row->setAttribute('worked_minutes', $start->diffInMinutes($end));
                }

                return $row;
            });

        $appointmentsQuery = Appointment::query()
            ->where('assigned_staff_id', $staff->id)
            ->where('date', '>=', $period->start->toDateString())
            ->where('date', '<', $period->endExclusive->toDateString());
        $appointmentCount = (clone $appointmentsQuery)->count();

        return view('admin.staff.show', [
            'staffMember' => $staff,
            'period' => $period,
            'billSummary' => $billSummary,
            'serviceRows' => $serviceRows,
            'attendanceRows' => $attendanceRows,
            'attendanceCounts' => $attendanceRows->countBy('status'),
            'appointmentCount' => $appointmentCount,
            'appointments' => $appointmentsQuery
                ->with(['customer', 'appointmentServices'])
                ->orderByDesc('date')
                ->orderBy('start_time')
                ->limit(100)
                ->get(),
        ]);
    }

    public function index(): View
    {
        $month = Carbon::now('Asia/Kolkata')->startOfMonth();
        $expectedStaff = User::query()
            ->where('role', 'staff')
            ->where('status', 'active')
            ->whereIn('username', ['staff1', 'staff2'])
            ->orderBy('username')
            ->get();

        if ($expectedStaff->isEmpty()) {
            $expectedStaff = User::query()
                ->where('role', 'staff')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        $attendanceRows = StaffAttendance::query()
            ->with('staff')
            ->whereIn('staff_id', $expectedStaff->pluck('id'))
            ->whereBetween('attendance_date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->get()
            ->groupBy(fn (StaffAttendance $row) => $row->attendance_date->toDateString());

        $calendar = collect(range(1, $month->daysInMonth))->map(function (int $day) use ($month, $expectedStaff, $attendanceRows) {
            $date = $month->copy()->day($day);
            $rows = $attendanceRows->get($date->toDateString(), collect())->keyBy('staff_id');
            $details = $expectedStaff->map(function (User $member) use ($rows) {
                $row = $rows->get($member->id);
                $present = in_array($row?->status, ['present', 'late'], true);

                return [
                    'name' => $member->name,
                    'status' => $present ? 'Present' : ($row?->status ? Str::headline($row->status) : 'Absent'),
                    'present' => $present,
                    'time' => $row?->check_in_time,
                    'selfie_url' => $row?->selfie_path ? route('admin.attendance.selfie', $row) : null,
                ];
            });

            $presentCount = $details->where('present', true)->count();
            $state = $date->isFuture()
                ? 'future'
                : ($presentCount === $expectedStaff->count() ? 'present' : ($presentCount === 0 ? 'absent' : 'mixed'));

            return [
                'date' => $date->toDateString(),
                'day' => $day,
                'state' => $state,
                'details' => $details->values(),
            ];
        });

        return view('admin.staff.index', [
            'staff' => User::query()
                ->where('role', 'staff')
                ->orderBy('name')
                ->paginate(20),
            'attendanceMonth' => $month,
            'attendanceCalendar' => $calendar,
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create', ['temporaryPassword' => Str::password(12)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'lowercase', 'max:255', Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'mobile' => ['required', 'string', 'max:30'],
            'employee_code' => ['nullable', 'string', 'max:255', Rule::unique('users', 'employee_code')],
            'specialization' => ['nullable', 'string', 'max:255'],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'is_home_service_eligible' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        User::query()->create([
            ...$validated,
            'role' => 'staff',
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
            'is_home_service_eligible' => $request->boolean('is_home_service_eligible'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account created. Share the temporary password securely; it will not be shown again.')
            ->with('temporary_password', $validated['password'])
            ->with('temporary_username', $validated['username']);
    }

    public function selfie(StaffAttendance $attendance)
    {
        abort_if(blank($attendance->selfie_path) || ! Storage::disk('local')->exists($attendance->selfie_path), 404);

        return Storage::disk('local')->response($attendance->selfie_path);
    }
}

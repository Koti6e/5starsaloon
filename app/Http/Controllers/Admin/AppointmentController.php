<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Support\ReportingPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    private const STATUSES = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'filter' => ['nullable', 'in:all,today,upcoming,pending,ongoing,completed,cancelled,home_visits'],
            'q' => ['nullable', 'string', 'max:100'],
            'view' => ['nullable', 'in:list,calendar'],
        ]);
        $today = now(ReportingPeriod::TIMEZONE)->toDateString();
        $period = ReportingPeriod::fromInput($filters);
        $filter = $filters['filter'] ?? 'all';
        $search = trim((string) ($filters['q'] ?? ''));
        $viewMode = ($filters['view'] ?? 'list') === 'calendar' && $period->mode !== 'custom' ? 'calendar' : 'list';

        $appointmentQuery = Appointment::query()
            ->with(['customer', 'assignedStaff', 'appointmentServices.service', 'bills'])
            ->where('date', '>=', $period->start->toDateString())
            ->where('date', '<', $period->endExclusive->toDateString())
            ->when($filter === 'today', fn ($query) => $query->whereDate('date', $today))
            ->when($filter === 'upcoming', fn ($query) => $query->whereDate('date', '>', $today))
            ->when($filter === 'pending', fn ($query) => $query->where('status', 'pending'))
            ->when($filter === 'ongoing', fn ($query) => $query->whereIn('status', ['confirmed', 'in_progress']))
            ->when($filter === 'completed', fn ($query) => $query->where('status', 'completed'))
            ->when($filter === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->when($filter === 'home_visits', fn ($query) => $query->where('appointment_type', 'home_service'))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('booking_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                    );
            }))
            ->orderByDesc('date')
            ->orderBy('start_time');

        $appointments = $viewMode === 'calendar'
            ? $appointmentQuery->get()
            : $appointmentQuery->paginate(20)->withQueryString();

        $calendarDays = collect();
        if ($viewMode === 'calendar') {
            $gridStart = match ($period->mode) {
                'daily' => $period->start,
                'weekly' => $period->start,
                default => $period->start->startOfMonth()->startOfWeek(\Carbon\CarbonImmutable::MONDAY),
            };
            $gridEnd = match ($period->mode) {
                'daily' => $period->endExclusive,
                'weekly' => $period->endExclusive,
                default => $period->endExclusive->subDay()->endOfMonth()->endOfWeek(\Carbon\CarbonImmutable::SUNDAY)->addDay()->startOfDay(),
            };
            $calendarAppointments = $appointments->groupBy(fn (Appointment $appointment) => $appointment->date->toDateString());
            for ($day = $gridStart; $day < $gridEnd; $day = $day->addDay()) {
                $calendarDays->push([
                    'date' => $day,
                    'inPeriod' => $day >= $period->start && $day < $period->endExclusive,
                    'appointments' => $calendarAppointments->get($day->toDateString(), collect()),
                ]);
            }
        }

        $counts = [
            'total' => Appointment::query()->whereBetween('date', [$period->start->toDateString(), $period->endExclusive->subDay()->toDateString()])->count(),
            'pending' => Appointment::query()->whereBetween('date', [$period->start->toDateString(), $period->endExclusive->subDay()->toDateString()])->where('status', 'pending')->count(),
            'ongoing' => Appointment::query()->whereBetween('date', [$period->start->toDateString(), $period->endExclusive->subDay()->toDateString()])->whereIn('status', ['confirmed', 'in_progress'])->count(),
            'completed' => Appointment::query()->whereBetween('date', [$period->start->toDateString(), $period->endExclusive->subDay()->toDateString()])->where('status', 'completed')->count(),
            'cancelled' => Appointment::query()->whereBetween('date', [$period->start->toDateString(), $period->endExclusive->subDay()->toDateString()])->where('status', 'cancelled')->count(),
            'home_visits' => Appointment::query()->whereBetween('date', [$period->start->toDateString(), $period->endExclusive->subDay()->toDateString()])->where('appointment_type', 'home_service')->count(),
        ];

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'counts' => $counts,
            'filter' => $filter,
            'period' => $period,
            'search' => $search,
            'viewMode' => $viewMode,
            'calendarDays' => $calendarDays,
            'activeStaff' => $this->activeStaff(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(Appointment $appointment): View
    {
        $appointment->load(['customer', 'assignedStaff', 'appointmentServices.service', 'bills']);

        return view('admin.appointments.show', [
            'appointment' => $appointment,
            'activeStaff' => $this->activeStaff(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function assign(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_staff_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'staff')
                    ->where('status', 'active')),
            ],
        ]);

        $appointment->update(['assigned_staff_id' => $validated['assigned_staff_id']]);

        return back()->with('status', 'Appointment staff assignment updated.');
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $nextStatus = $validated['status'];

        if (! $this->canTransition($appointment->status, $nextStatus)) {
            throw ValidationException::withMessages([
                'status' => 'This appointment cannot move from '.str_replace('_', ' ', $appointment->status).' to '.str_replace('_', ' ', $nextStatus).'.',
            ]);
        }

        $appointment->update(['status' => $nextStatus]);

        return back()->with('status', 'Appointment status updated.');
    }

    private function activeStaff()
    {
        return User::query()
            ->where('role', 'staff')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function canTransition(string $currentStatus, string $nextStatus): bool
    {
        if ($currentStatus === $nextStatus) {
            return true;
        }

        if (in_array($currentStatus, ['completed', 'cancelled'], true)) {
            return false;
        }

        return match ($currentStatus) {
            'pending' => in_array($nextStatus, ['confirmed', 'cancelled'], true),
            'confirmed' => in_array($nextStatus, ['in_progress', 'completed', 'cancelled'], true),
            'in_progress' => in_array($nextStatus, ['completed', 'cancelled'], true),
            default => false,
        };
    }
}

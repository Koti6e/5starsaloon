<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
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
        $today = now('Asia/Kolkata')->toDateString();
        $filter = $request->query('filter', 'all');
        $search = trim((string) $request->query('q', ''));

        $appointments = Appointment::query()
            ->with(['customer', 'assignedStaff', 'appointmentServices.service', 'bills'])
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
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'total' => Appointment::query()->count(),
            'pending' => Appointment::query()->where('status', 'pending')->count(),
            'ongoing' => Appointment::query()->whereIn('status', ['confirmed', 'in_progress'])->count(),
            'completed' => Appointment::query()->where('status', 'completed')->count(),
            'cancelled' => Appointment::query()->where('status', 'cancelled')->count(),
            'home_visits' => Appointment::query()->where('appointment_type', 'home_service')->count(),
        ];

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'counts' => $counts,
            'filter' => $filter,
            'search' => $search,
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

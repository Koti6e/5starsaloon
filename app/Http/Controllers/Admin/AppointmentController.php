<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
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
        ]);
    }

    public function show(Appointment $appointment): View
    {
        $appointment->load(['customer', 'assignedStaff', 'appointmentServices.service', 'bills']);

        return view('admin.appointments.show', [
            'appointment' => $appointment,
        ]);
    }
}

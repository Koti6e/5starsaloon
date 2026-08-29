<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AppointmentNotificationService
{
    public function __construct(private FirebaseCloudMessaging $fcm) {}

    public function notifyCaptain(Appointment $appointment): void
    {
        $appointment->loadMissing(['customer', 'appointmentServices']);
        $admins = User::query()->where('role', 'admin')->where('status', 'active')->with('fcmDeviceTokens')->get();

        foreach ($admins as $admin) {
            $notification = AppNotification::query()->firstOrCreate(
                [
                    'user_id' => $admin->id,
                    'appointment_id' => $appointment->id,
                    'type' => 'appointment.created',
                ],
                [
                    'title' => 'New Appointment',
                    'body' => $this->body($appointment),
                    'action_url' => route('admin.appointments.show', $appointment),
                    'payload' => [
                        'appointment_id' => $appointment->id,
                        'booking_number' => $appointment->booking_number,
                    ],
                ],
            );

            if (! $notification->wasRecentlyCreated || $notification->fcm_sent_at) {
                continue;
            }

            $sent = false;
            foreach ($admin->fcmDeviceTokens->whereNull('revoked_at') as $deviceToken) {
                $sent = $this->fcm->send(
                    $deviceToken,
                    $notification->title,
                    $notification->body,
                    $notification->action_url,
                    $notification->payload ?? [],
                ) || $sent;
            }

            if ($sent) {
                $notification->forceFill(['fcm_sent_at' => now()])->save();
            }
        }
    }

    private function body(Appointment $appointment): string
    {
        $serviceNames = $appointment->appointmentServices->pluck('service_name_snapshot')->filter()->join(', ') ?: 'Salon service';
        $date = $appointment->date?->timezone('Asia/Kolkata')->format('d M Y') ?? (string) $appointment->date;
        $time = \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A');

        return "{$appointment->customer->name} has booked an appointment. {$date} · {$time} · {$serviceNames}";
    }
}

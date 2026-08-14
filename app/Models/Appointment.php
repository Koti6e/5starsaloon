<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'confirmation_token',
        'customer_id',
        'appointment_type',
        'date',
        'start_time',
        'estimated_end_time',
        'assigned_staff_id',
        'subtotal',
        'visit_charge',
        'discount',
        'total',
        'status',
        'customer_notes',
        'internal_notes',
        'cancellation_reason',
        'address_line_1',
        'address_line_2',
        'landmark',
        'area',
        'city',
        'state',
        'pincode',
        'maps_url',
        'additional_directions',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'subtotal' => 'decimal:2',
            'visit_charge' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function appointmentServices(): HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}

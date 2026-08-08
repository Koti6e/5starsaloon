<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentService extends Model
{
    protected $fillable = [
        'appointment_id',
        'service_id',
        'service_name_snapshot',
        'unit_price',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}

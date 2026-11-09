<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $fillable = [
        'staff_id',
        'attendance_date',
        'status',
        'check_in_time',
        'check_out_time',
        'source',
        'login_session_id',
        'selfie_path',
        'selfie_captured_at',
        'check_in_latitude', 'check_in_longitude', 'check_in_gps_accuracy', 'check_in_distance_from_salon', 'check_in_gps_verification_status', 'check_in_location_captured_at',
        'check_out_latitude', 'check_out_longitude', 'check_out_gps_accuracy', 'check_out_distance_from_salon', 'check_out_gps_verification_status', 'check_out_location_captured_at',
        'notes',
        'marked_by',
        'corrected_by',
        'correction_reason',
        'corrected_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'selfie_captured_at' => 'datetime',
            'check_in_location_captured_at' => 'datetime',
            'check_out_location_captured_at' => 'datetime',
            'corrected_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}

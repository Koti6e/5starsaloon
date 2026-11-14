<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceVerificationAttempt extends Model
{
    protected $fillable = [
        'staff_id', 'attendance_id', 'device_id', 'attempt_type', 'latitude', 'longitude', 'gps_accuracy_meters',
        'gps_position_at', 'distance_from_salon_meters', 'decision', 'reason', 'selfie_path', 'attempted_at',
        'reviewed_by', 'reviewed_at', 'review_reason',
    ];

    protected function casts(): array
    {
        return ['gps_position_at' => 'datetime', 'attempted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}

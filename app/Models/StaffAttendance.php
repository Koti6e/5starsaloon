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

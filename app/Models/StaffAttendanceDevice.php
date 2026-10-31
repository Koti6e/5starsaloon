<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendanceDevice extends Model
{
    protected $fillable = [
        'staff_id', 'device_key_hash', 'label', 'status', 'first_seen_at', 'last_seen_at',
        'approved_by', 'approved_at', 'revoked_by', 'revoked_at',
    ];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'approved_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}

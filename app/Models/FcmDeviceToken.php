<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmDeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'platform',
        'user_agent',
        'last_seen_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

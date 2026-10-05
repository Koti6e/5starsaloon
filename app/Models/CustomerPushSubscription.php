<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPushSubscription extends Model
{
    protected $fillable = [
        'token',
        'token_hash',
        'platform',
        'user_agent',
        'consented_at',
        'last_seen_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'consented_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}

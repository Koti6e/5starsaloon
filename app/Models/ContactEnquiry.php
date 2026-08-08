<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactEnquiry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'subject',
        'message',
        'consented_at',
        'status',
        'internal_notes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime'];
    }
}

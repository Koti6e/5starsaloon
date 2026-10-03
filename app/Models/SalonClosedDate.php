<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonClosedDate extends Model
{
    protected $fillable = ['date', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['date' => 'date', 'is_active' => 'boolean'];
    }
}

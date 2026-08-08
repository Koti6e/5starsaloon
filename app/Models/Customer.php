<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** @use HasFactory<\Database\Factories\CustomerFactory> */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'name',
        'mobile',
        'alternate_mobile',
        'email',
        'gender',
        'date_of_birth',
        'anniversary_date',
        'address_line_1',
        'address_line_2',
        'landmark',
        'area',
        'city',
        'state',
        'pincode',
        'notes',
        'total_visits',
        'total_spent',
        'last_visit_at',
        'status',
    ];

    public static function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?: '';

        if (strlen($digits) > 10 && str_starts_with($digits, '91')) {
            $digits = substr($digits, -10);
        }

        return $digits;
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'anniversary_date' => 'date',
            'last_visit_at' => 'datetime',
            'total_spent' => 'decimal:2',
        ];
    }
}

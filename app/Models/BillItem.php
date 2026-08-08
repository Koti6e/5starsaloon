<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $fillable = [
        'bill_id',
        'service_id',
        'service_performed_by',
        'service_name_snapshot',
        'service_code_snapshot',
        'category_name_snapshot',
        'is_package_snapshot',
        'quantity',
        'unit_price',
        'discount_amount',
        'line_total',
        'price_was_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'is_package_snapshot' => 'boolean',
            'price_was_confirmed' => 'boolean',
        ];
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_performed_by');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}

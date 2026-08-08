<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'category', 'description', 'image', 'alt_text', 'display_order', 'is_featured', 'status'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodCoupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}

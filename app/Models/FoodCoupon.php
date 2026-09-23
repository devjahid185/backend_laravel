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
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'minimum_order' => 'decimal:2',
    ];
}

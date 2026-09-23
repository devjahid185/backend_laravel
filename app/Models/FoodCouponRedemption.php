<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodCouponRedemption extends Model
{
    protected $guarded = [];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'admin_discount_amount' => 'decimal:2',
        'restaurant_discount_amount' => 'decimal:2',
        'delivery_discount_amount' => 'decimal:2',
        'breakdown' => 'array',
    ];
}

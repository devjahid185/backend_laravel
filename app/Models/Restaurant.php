<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cuisines' => 'array',
        'features' => 'array',
        'delivery_available' => 'boolean',
        'accepts_food_orders' => 'boolean',
        'commission_enabled' => 'boolean',
        'cod_enabled' => 'boolean',
        'takeaway_available' => 'boolean',
        'dine_in_available' => 'boolean',
        'service_radius_km' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_fixed_fee' => 'decimal:2',
    ];

    public function foodItems()
    {
        return $this->hasMany(FoodItem::class);
    }

    public function foodOrders()
    {
        return $this->hasMany(FoodOrder::class);
    }
}

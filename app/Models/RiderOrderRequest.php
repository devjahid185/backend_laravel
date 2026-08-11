<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderOrderRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'restaurant_lat' => 'decimal:7',
        'restaurant_lng' => 'decimal:7',
        'notified_at' => 'datetime',
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }
}

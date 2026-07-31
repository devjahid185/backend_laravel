<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodCartItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'add_ons' => 'array',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class);
    }
}

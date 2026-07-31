<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'size_options' => 'array',
        'spice_options' => 'array',
        'add_ons' => 'array',
        'is_available' => 'boolean',
        'is_popular' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(FoodCategory::class, 'food_category_id');
    }
}

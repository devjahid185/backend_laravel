<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodCart extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(FoodCartItem::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}

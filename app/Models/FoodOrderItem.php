<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'add_ons' => 'array',
    ];
}

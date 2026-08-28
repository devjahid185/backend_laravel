<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodOrderSupportTicket extends Model
{
    protected $guarded = [];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

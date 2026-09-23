<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderWalletEntry extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'paid_out_at' => 'datetime',
    ];

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function order()
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }
}

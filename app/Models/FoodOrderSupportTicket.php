<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodOrderSupportTicket extends Model
{
    protected $guarded = [];

    protected $casts = [
        'resolved_at' => 'datetime',
        'admin_replied_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repliedBy()
    {
        return $this->belongsTo(Admin::class, 'admin_replied_by_id');
    }
}

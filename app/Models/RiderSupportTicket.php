<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderSupportTicket extends Model
{
    protected $guarded = [];

    protected $casts = [
        'admin_replied_at' => 'datetime',
    ];

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function order()
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    public function repliedBy()
    {
        return $this->belongsTo(Admin::class, 'admin_replied_by_id');
    }
}

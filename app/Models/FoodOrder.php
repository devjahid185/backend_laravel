<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodOrder extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'delivery_otp',
        'delivery_otp_send_error',
    ];

    protected $casts = [
        'estimated_delivery_at' => 'datetime',
        'accepted_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_otp_sent_at' => 'datetime',
        'delivery_otp_expires_at' => 'datetime',
        'delivery_otp_send_failed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(FoodOrderItem::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function riderRequests()
    {
        return $this->hasMany(RiderOrderRequest::class);
    }

    public function address()
    {
        return $this->belongsTo(FoodAddress::class, 'food_address_id');
    }

    public function supportTickets()
    {
        return $this->hasMany(FoodOrderSupportTicket::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineOrder extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'delivery_otp',
        'delivery_otp_send_error',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_otp_sent_at' => 'datetime',
        'delivery_otp_expires_at' => 'datetime',
        'delivery_otp_send_failed_at' => 'datetime',
        'bkash_raw' => 'array',
        'bkash_paid_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(MedicineOrderItem::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function riderRequests()
    {
        return $this->hasMany(RiderOrderRequest::class);
    }
}

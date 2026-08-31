<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'accepted_at' => 'datetime',
        'delivered_at' => 'datetime',
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

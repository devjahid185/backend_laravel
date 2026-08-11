<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderLocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'accuracy' => 'decimal:2',
    ];
}

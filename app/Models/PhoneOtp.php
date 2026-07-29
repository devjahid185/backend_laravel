<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneOtp extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'purpose',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaunchService extends Model
{
    use HasFactory;

    protected $table = 'launches';
    protected $guarded = [];

    protected $casts = [
        'phones' => 'array',
        'has_cabin' => 'boolean',
        'has_ac' => 'boolean',
        'has_food' => 'boolean',
        'online_booking' => 'boolean',
        'deck_fare' => 'decimal:2',
        'chair_fare' => 'decimal:2',
        'single_cabin_fare' => 'decimal:2',
        'double_cabin_fare' => 'decimal:2',
    ];
}
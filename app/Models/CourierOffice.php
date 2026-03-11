<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierOffice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'phones' => 'array',
        'services' => 'array',
    ];
}

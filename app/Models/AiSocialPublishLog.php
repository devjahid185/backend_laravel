<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSocialPublishLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}

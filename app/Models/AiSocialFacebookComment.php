<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSocialFacebookComment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'public_replied_at' => 'datetime',
        'private_replied_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];
}

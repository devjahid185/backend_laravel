<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSocialCommentRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'ai_enabled' => 'boolean',
        'auto_public_reply' => 'boolean',
        'auto_private_reply' => 'boolean',
        'sort_order' => 'integer',
    ];
}

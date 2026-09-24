<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSocialPost extends Model
{
    protected $guarded = [];

    protected $casts = [
        'source_snapshot' => 'array',
        'hashtags' => 'array',
        'ai_response' => 'array',
        'scheduled_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(AiSocialPublishLog::class);
    }
}

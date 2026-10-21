<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiNotificationDecision extends Model
{
    protected $guarded = [];

    protected $casts = [
        'score' => 'decimal:2',
        'context' => 'array',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'converted_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notification()
    {
        return $this->belongsTo(AppNotification::class, 'app_notification_id');
    }
}

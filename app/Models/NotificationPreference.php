<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'push_enabled',
        'sms_enabled',
        'email_enabled',
        'marketing_enabled',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'marketing_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

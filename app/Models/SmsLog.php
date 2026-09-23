<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'phone',
        'message',
        'purpose',
        'provider',
        'sender_id',
        'api_url',
        'status',
        'http_status',
        'gateway_response',
        'error_message',
        'request_payload',
        'sent_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'sent_at' => 'datetime',
    ];
}

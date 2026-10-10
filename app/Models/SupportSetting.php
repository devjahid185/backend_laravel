<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportSetting extends Model
{
    protected $fillable = [
        'phone',
        'email',
        'whatsapp',
        'facebook_group_prompt_enabled',
        'facebook_group_url',
        'availability',
        'note',
    ];

    protected $casts = [
        'facebook_group_prompt_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'email' => 'support@bholavashi.site',
                'availability' => 'প্রতিদিন সকাল ৯টা থেকে রাত ১০টা',
                'facebook_group_url' => 'https://www.facebook.com/share/g/1Eyr3yxGqM/',
            ]
        );
    }
}

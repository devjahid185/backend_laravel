<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportSetting extends Model
{
    protected $fillable = [
        'phone',
        'email',
        'whatsapp',
        'availability',
        'note',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'email' => 'support@bholavashi.site',
                'availability' => 'প্রতিদিন সকাল ৯টা থেকে রাত ১০টা',
            ]
        );
    }
}

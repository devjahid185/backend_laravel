<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'mailer',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'timeout',
        'last_tested_at',
        'last_test_result',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'password' => 'encrypted',
            'port' => 'integer',
            'timeout' => 'integer',
            'last_tested_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => (bool) config('mail.enabled', false),
                'mailer' => config('mail.default', 'smtp'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => (int) config('mail.mailers.smtp.port', 587),
                'username' => config('mail.mailers.smtp.username'),
                'password' => config('mail.mailers.smtp.password'),
                'encryption' => config('mail.mailers.smtp.scheme'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name', 'Bholabashi'),
                'timeout' => null,
            ]
        );
    }

    public function maskedPassword(): ?string
    {
        if (! $this->password) {
            return null;
        }

        $length = strlen($this->password);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return substr($this->password, 0, 2).str_repeat('*', max(0, $length - 4)).substr($this->password, -2);
    }
}

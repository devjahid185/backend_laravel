<?php

namespace App\Services;

use App\Models\EmailSetting;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendText(string $to, string $subject, string $body): void
    {
        $settings = EmailSetting::current();

        if (! $settings->is_enabled) {
            throw new \RuntimeException('Email sending is disabled.');
        }

        if (! $settings->from_address) {
            throw new \RuntimeException('Email from address is not configured.');
        }

        if ($settings->mailer === 'smtp' && (! $settings->host || ! $settings->port)) {
            throw new \RuntimeException('SMTP host and port are required.');
        }

        $this->applyRuntimeConfig($settings);

        Mail::raw($body, function ($message) use ($to, $subject, $settings): void {
            $message->to($to)
                ->from($settings->from_address, $settings->from_name ?: 'Bholabashi')
                ->subject($subject);
        });
    }

    private function applyRuntimeConfig(EmailSetting $settings): void
    {
        $scheme = match ($settings->encryption) {
            'ssl' => 'smtps',
            'tls', 'starttls' => 'smtp',
            default => null,
        };

        config([
            'mail.default' => $settings->mailer ?: 'smtp',
            'mail.from.address' => $settings->from_address,
            'mail.from.name' => $settings->from_name ?: 'Bholabashi',
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port,
            'mail.mailers.smtp.username' => $settings->username,
            'mail.mailers.smtp.password' => $settings->password,
            'mail.mailers.smtp.scheme' => $scheme ?: null,
            'mail.mailers.smtp.timeout' => $settings->timeout,
        ]);

        app('mail.manager')->purge('smtp');
    }
}

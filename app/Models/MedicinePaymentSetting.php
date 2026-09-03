<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicinePaymentSetting extends Model
{
    protected $fillable = [
        'cod_enabled',
        'manual_bkash_enabled',
        'manual_nagad_enabled',
        'online_enabled',
        'bkash_tokenized_enabled',
        'bkash_tokenized_sandbox',
        'require_manual_payment_proof',
        'cod_title',
        'manual_bkash_title',
        'manual_nagad_title',
        'online_title',
        'bkash_tokenized_title',
        'bkash_number',
        'nagad_number',
        'bkash_tokenized_base_url',
        'bkash_tokenized_callback_url',
        'bkash_tokenized_app_key',
        'bkash_tokenized_app_secret',
        'bkash_tokenized_username',
        'bkash_tokenized_password',
        'cod_instructions',
        'manual_bkash_instructions',
        'manual_nagad_instructions',
        'online_instructions',
        'bkash_tokenized_instructions',
        'payment_notice',
    ];

    protected function casts(): array
    {
        return [
            'cod_enabled' => 'boolean',
            'manual_bkash_enabled' => 'boolean',
            'manual_nagad_enabled' => 'boolean',
            'online_enabled' => 'boolean',
            'bkash_tokenized_enabled' => 'boolean',
            'bkash_tokenized_sandbox' => 'boolean',
            'require_manual_payment_proof' => 'boolean',
            'bkash_tokenized_app_key' => 'encrypted',
            'bkash_tokenized_app_secret' => 'encrypted',
            'bkash_tokenized_username' => 'encrypted',
            'bkash_tokenized_password' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'cod_enabled' => true,
                'manual_bkash_enabled' => filled(config('services.medicine_payment.bkash_number')),
                'manual_nagad_enabled' => filled(config('services.medicine_payment.nagad_number')),
                'online_enabled' => false,
                'bkash_tokenized_enabled' => false,
                'bkash_tokenized_sandbox' => true,
                'require_manual_payment_proof' => false,
                'cod_title' => 'Cash on Delivery',
                'manual_bkash_title' => 'Manual bKash',
                'manual_nagad_title' => 'Manual Nagad',
                'online_title' => 'Online Payment',
                'bkash_tokenized_title' => 'bKash Checkout',
                'bkash_tokenized_base_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout',
                'bkash_number' => config('services.medicine_payment.bkash_number'),
                'nagad_number' => config('services.medicine_payment.nagad_number'),
                'cod_instructions' => 'মেডিসিন হাতে পেয়ে টাকা দিন।',
                'manual_bkash_instructions' => config('services.medicine_payment.instructions') ?: 'Send Money করে transaction ID দিন।',
                'manual_nagad_instructions' => config('services.medicine_payment.instructions') ?: 'Send Money করে transaction ID দিন।',
                'bkash_tokenized_instructions' => 'bKash checkout পেজে নিরাপদে পেমেন্ট সম্পন্ন করুন।',
            ]
        );
    }

    public function paymentOptions(): array
    {
        $options = [];
        if ($this->cod_enabled) {
            $options[] = [
                'method' => 'cash_on_delivery',
                'title' => $this->cod_title ?: 'Cash on Delivery',
                'subtitle' => $this->cod_instructions ?: 'মেডিসিন হাতে পেয়ে টাকা দিন।',
                'number' => null,
                'instructions' => $this->cod_instructions,
                'requires_proof' => false,
            ];
        }

        if ($this->manual_bkash_enabled && filled($this->bkash_number)) {
            $options[] = [
                'method' => 'manual_bkash',
                'title' => $this->manual_bkash_title ?: 'Manual bKash',
                'subtitle' => 'এই নম্বরে পেমেন্ট করুন',
                'number' => $this->bkash_number,
                'instructions' => $this->manual_bkash_instructions ?: 'Send Money করে transaction ID দিন।',
                'requires_proof' => (bool) $this->require_manual_payment_proof,
            ];
        }

        if ($this->manual_nagad_enabled && filled($this->nagad_number)) {
            $options[] = [
                'method' => 'manual_nagad',
                'title' => $this->manual_nagad_title ?: 'Manual Nagad',
                'subtitle' => 'এই নম্বরে পেমেন্ট করুন',
                'number' => $this->nagad_number,
                'instructions' => $this->manual_nagad_instructions ?: 'Send Money করে transaction ID দিন।',
                'requires_proof' => (bool) $this->require_manual_payment_proof,
            ];
        }

        if ($this->online_enabled) {
            $options[] = [
                'method' => 'online',
                'title' => $this->online_title ?: 'Online Payment',
                'subtitle' => $this->online_instructions ?: 'Pay online before order confirmation.',
                'number' => null,
                'instructions' => $this->online_instructions,
                'requires_proof' => false,
            ];
        }

        if ($this->bkash_tokenized_enabled && $this->hasBkashTokenizedCredentials()) {
            $options[] = [
                'method' => 'bkash_tokenized',
                'title' => $this->bkash_tokenized_title ?: 'bKash Checkout',
                'subtitle' => $this->bkash_tokenized_instructions ?: 'Pay securely with bKash Checkout.',
                'number' => null,
                'instructions' => $this->bkash_tokenized_instructions,
                'requires_proof' => false,
                'opens_url' => true,
            ];
        }

        return $options;
    }

    public function hasBkashTokenizedCredentials(): bool
    {
        return filled($this->safeSecret('bkash_tokenized_app_key'))
            && filled($this->safeSecret('bkash_tokenized_app_secret'))
            && filled($this->safeSecret('bkash_tokenized_username'))
            && filled($this->safeSecret('bkash_tokenized_password'));
    }

    public function bkashBaseUrl(): string
    {
        if ($this->bkash_tokenized_base_url) {
            return rtrim($this->bkash_tokenized_base_url, '/');
        }

        return $this->bkash_tokenized_sandbox
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';
    }

    public function maskedSecret(string $key): ?string
    {
        $value = $this->safeSecret($key);
        if (! $value) {
            return null;
        }
        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        return substr($value, 0, 4).str_repeat('*', max(0, $length - 8)).substr($value, -4);
    }

    public function safeSecret(string $key): ?string
    {
        try {
            return $this->{$key};
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return null;
        }
    }
}

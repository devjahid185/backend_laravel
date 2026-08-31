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
        'require_manual_payment_proof',
        'cod_title',
        'manual_bkash_title',
        'manual_nagad_title',
        'online_title',
        'bkash_number',
        'nagad_number',
        'cod_instructions',
        'manual_bkash_instructions',
        'manual_nagad_instructions',
        'online_instructions',
        'payment_notice',
    ];

    protected function casts(): array
    {
        return [
            'cod_enabled' => 'boolean',
            'manual_bkash_enabled' => 'boolean',
            'manual_nagad_enabled' => 'boolean',
            'online_enabled' => 'boolean',
            'require_manual_payment_proof' => 'boolean',
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
                'require_manual_payment_proof' => false,
                'cod_title' => 'Cash on Delivery',
                'manual_bkash_title' => 'Manual bKash',
                'manual_nagad_title' => 'Manual Nagad',
                'online_title' => 'Online Payment',
                'bkash_number' => config('services.medicine_payment.bkash_number'),
                'nagad_number' => config('services.medicine_payment.nagad_number'),
                'cod_instructions' => 'মেডিসিন হাতে পেয়ে টাকা দিন।',
                'manual_bkash_instructions' => config('services.medicine_payment.instructions') ?: 'Send Money করে transaction ID দিন।',
                'manual_nagad_instructions' => config('services.medicine_payment.instructions') ?: 'Send Money করে transaction ID দিন।',
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

        return $options;
    }
}

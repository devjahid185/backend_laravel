<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicinePaymentSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMedicinePaymentSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = MedicinePaymentSetting::current();

        return response()->json([
            'settings' => $this->serialize($settings),
            'payment_options' => $settings->paymentOptions(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cod_enabled' => ['required', 'boolean'],
            'manual_bkash_enabled' => ['required', 'boolean'],
            'manual_nagad_enabled' => ['required', 'boolean'],
            'online_enabled' => ['required', 'boolean'],
            'require_manual_payment_proof' => ['nullable', 'boolean'],
            'cod_title' => ['nullable', 'string', 'max:80'],
            'manual_bkash_title' => ['nullable', 'string', 'max:80'],
            'manual_nagad_title' => ['nullable', 'string', 'max:80'],
            'online_title' => ['nullable', 'string', 'max:80'],
            'bkash_number' => ['nullable', 'string', 'max:40'],
            'nagad_number' => ['nullable', 'string', 'max:40'],
            'cod_instructions' => ['nullable', 'string', 'max:2000'],
            'manual_bkash_instructions' => ['nullable', 'string', 'max:2000'],
            'manual_nagad_instructions' => ['nullable', 'string', 'max:2000'],
            'online_instructions' => ['nullable', 'string', 'max:2000'],
            'payment_notice' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings = MedicinePaymentSetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Medicine payment settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
            'payment_options' => $settings->fresh()->paymentOptions(),
        ]);
    }

    private function serialize(MedicinePaymentSetting $settings): array
    {
        return [
            'cod_enabled' => (bool) $settings->cod_enabled,
            'manual_bkash_enabled' => (bool) $settings->manual_bkash_enabled,
            'manual_nagad_enabled' => (bool) $settings->manual_nagad_enabled,
            'online_enabled' => (bool) $settings->online_enabled,
            'require_manual_payment_proof' => (bool) $settings->require_manual_payment_proof,
            'cod_title' => $settings->cod_title,
            'manual_bkash_title' => $settings->manual_bkash_title,
            'manual_nagad_title' => $settings->manual_nagad_title,
            'online_title' => $settings->online_title,
            'bkash_number' => $settings->bkash_number,
            'nagad_number' => $settings->nagad_number,
            'cod_instructions' => $settings->cod_instructions,
            'manual_bkash_instructions' => $settings->manual_bkash_instructions,
            'manual_nagad_instructions' => $settings->manual_nagad_instructions,
            'online_instructions' => $settings->online_instructions,
            'payment_notice' => $settings->payment_notice,
            'updated_at' => optional($settings->updated_at)->toIso8601String(),
        ];
    }
}

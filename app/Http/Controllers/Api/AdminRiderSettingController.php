<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiderSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRiderSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['settings' => $this->serialize(RiderSetting::current())]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'commission_title' => ['nullable', 'string', 'max:160'],
            'commission_description' => ['nullable', 'string'],
            'agreement_title' => ['nullable', 'string', 'max:160'],
            'agreement_terms' => ['nullable', 'string'],
            'cash_policy' => ['nullable', 'string'],
            'penalty_policy' => ['nullable', 'string'],
        ]);

        $settings = RiderSetting::current();
        $settings->update($data);

        return response()->json([
            'message' => 'Rider settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    private function serialize(RiderSetting $settings): array
    {
        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'commission_title' => $settings->commission_title,
            'commission_description' => $settings->commission_description,
            'agreement_title' => $settings->agreement_title,
            'agreement_terms' => $settings->agreement_terms,
            'cash_policy' => $settings->cash_policy,
            'penalty_policy' => $settings->penalty_policy,
        ];
    }
}

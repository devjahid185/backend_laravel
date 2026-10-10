<?php

namespace App\Http\Controllers\Api;

use App\Models\SupportSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSupportSettingController extends SupportSettingController
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'facebook_group_prompt_enabled' => ['nullable', 'boolean'],
            'facebook_group_url' => ['nullable', 'url', 'max:500'],
            'availability' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['facebook_group_prompt_enabled'] = (bool) ($validated['facebook_group_prompt_enabled'] ?? false);

        $settings = SupportSetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Support settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }
}

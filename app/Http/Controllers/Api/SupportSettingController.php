<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportSetting;
use Illuminate\Http\JsonResponse;

class SupportSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => $this->serialize(SupportSetting::current()),
        ]);
    }

    protected function serialize(SupportSetting $settings): array
    {
        return [
            'phone' => $settings->phone,
            'email' => $settings->email,
            'whatsapp' => $settings->whatsapp,
            'availability' => $settings->availability,
            'note' => $settings->note,
        ];
    }
}

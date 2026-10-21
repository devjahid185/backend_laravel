<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliverySetting;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminFoodDeliverySettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => $this->serialize(FoodDeliverySetting::current()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'charge_mode' => ['required', 'in:fixed,per_km'],
            'municipality_rule_enabled' => ['nullable', 'boolean'],
            'municipality_fixed_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'rider_fixed_earning' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'municipality_extra_per_km_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'municipality_long_distance_enabled' => ['nullable', 'boolean'],
            'municipality_long_distance_threshold_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'municipality_long_distance_per_km_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'rider_per_km_earning' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'municipality_center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'municipality_center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'municipality_radius_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'municipality_polygon' => ['nullable', 'array'],
            'municipality_polygon.*.lat' => ['required_with:municipality_polygon', 'numeric', 'between:-90,90'],
            'municipality_polygon.*.lng' => ['required_with:municipality_polygon', 'numeric', 'between:-180,180'],
            'fixed_charge' => ['required', 'numeric', 'min:0', 'max:10000'],
            'base_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'per_km_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'minimum_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'free_delivery_min_order' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'max_delivery_distance_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'store_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'store_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'checkout_image_notice_enabled' => ['nullable', 'boolean'],
            'checkout_image_notice_text' => ['nullable', 'string', 'max:1000'],
            'messenger_enabled' => ['nullable', 'boolean'],
            'messenger_url' => ['required_if:messenger_enabled,true', 'nullable', 'url', 'max:500'],
            'user_manual_video_enabled' => ['nullable', 'boolean'],
            'user_manual_video_title' => ['nullable', 'string', 'max:200'],
            'user_manual_video_description' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = FoodDeliverySetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Food delivery settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    public function uploadUserManualVideo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'video' => ['required', 'file', 'mimes:mp4', 'max:102400'],
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = FoodDeliverySetting::current();
        $oldPath = $settings->user_manual_video_path;
        $path = $request->file('video')->store('food/user-manual', 'public');

        $settings->update([
            'user_manual_video_path' => $path,
            'user_manual_video_enabled' => true,
            'user_manual_video_title' => $validated['title'] ?? $settings->user_manual_video_title,
            'user_manual_video_description' => $validated['description'] ?? $settings->user_manual_video_description,
        ]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'User manual video uploaded successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    public function deleteUserManualVideo(): JsonResponse
    {
        $settings = FoodDeliverySetting::current();
        if ($settings->user_manual_video_path) {
            Storage::disk('public')->delete($settings->user_manual_video_path);
        }
        $settings->update([
            'user_manual_video_path' => null,
            'user_manual_video_enabled' => false,
        ]);

        return response()->json([
            'message' => 'User manual video removed.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    private function serialize(FoodDeliverySetting $settings): array
    {
        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'charge_mode' => $settings->charge_mode,
            'municipality_rule_enabled' => (bool) $settings->municipality_rule_enabled,
            'municipality_fixed_charge' => (float) ($settings->municipality_fixed_charge ?? 50),
            'rider_fixed_earning' => (float) ($settings->rider_fixed_earning ?? $settings->municipality_fixed_charge ?? 50),
            'municipality_extra_per_km_charge' => (float) ($settings->municipality_extra_per_km_charge ?? 15),
            'municipality_long_distance_enabled' => (bool) ($settings->municipality_long_distance_enabled ?? false),
            'municipality_long_distance_threshold_km' => (float) ($settings->municipality_long_distance_threshold_km ?? 8),
            'municipality_long_distance_per_km_charge' => (float) ($settings->municipality_long_distance_per_km_charge ?? 15),
            'rider_per_km_earning' => (float) ($settings->rider_per_km_earning ?? $settings->municipality_extra_per_km_charge ?? 15),
            'municipality_center_lat' => $settings->municipality_center_lat === null ? null : (float) $settings->municipality_center_lat,
            'municipality_center_lng' => $settings->municipality_center_lng === null ? null : (float) $settings->municipality_center_lng,
            'municipality_radius_km' => $settings->municipality_radius_km === null ? null : (float) $settings->municipality_radius_km,
            'municipality_polygon' => $settings->municipality_polygon ?? [],
            'fixed_charge' => (float) $settings->fixed_charge,
            'base_charge' => (float) $settings->base_charge,
            'per_km_charge' => (float) $settings->per_km_charge,
            'minimum_charge' => (float) $settings->minimum_charge,
            'free_delivery_min_order' => $settings->free_delivery_min_order === null ? null : (float) $settings->free_delivery_min_order,
            'max_delivery_distance_km' => $settings->max_delivery_distance_km === null ? null : (float) $settings->max_delivery_distance_km,
            'store_lat' => $settings->store_lat === null ? null : (float) $settings->store_lat,
            'store_lng' => $settings->store_lng === null ? null : (float) $settings->store_lng,
            'checkout_image_notice_enabled' => (bool) ($settings->checkout_image_notice_enabled ?? true),
            'checkout_image_notice_text' => $settings->checkout_image_notice_text ?: 'খাবারের ছবিগুলো অনেক ক্ষেত্রে AI/ইলাস্ট্রেশন হিসেবে ব্যবহার করা হয়েছে। রেস্টুরেন্টের প্রস্তুত খাবার ছবির সাথে সামান্য ভিন্ন হতে পারে। অর্ডার নিশ্চিত করলে আপনি বিষয়টি বুঝে সম্মত হচ্ছেন।',
            'messenger_enabled' => (bool) ($settings->messenger_enabled ?? false),
            'messenger_url' => $settings->messenger_url,
            'user_manual_video_enabled' => (bool) ($settings->user_manual_video_enabled ?? false),
            'user_manual_video_url' => MediaUrl::toUrl($settings->user_manual_video_path),
            'user_manual_video_title' => $settings->user_manual_video_title ?: 'কীভাবে খাবার অর্ডার করবেন',
            'user_manual_video_description' => $settings->user_manual_video_description ?: 'ভোলাবাসী অ্যাপে খুব সহজে খাবার অর্ডার করার পুরো নিয়ম এই ভিডিওতে দেখুন।',
            'note' => $settings->note,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BloodDonor;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\JobPost;
use App\Models\MarketplaceItem;
use App\Models\CarRental;
use App\Models\MediaAsset;
use App\Models\Property;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Worker;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    /**
     * @var array<string, class-string>
     */
    private array $targetModelMap = [
        'user' => User::class,
        'worker' => Worker::class,
        'business' => Business::class,
        'blood_donor' => BloodDonor::class,
        'doctor' => Doctor::class,
        'hospital' => Hospital::class,
        'car_rental' => CarRental::class,
        'teacher' => Teacher::class,
        'marketplace_item' => MarketplaceItem::class,
        'property' => Property::class,
        'job_post' => JobPost::class,
    ];

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section' => ['required', 'string', 'max:80'],
            'target_type' => ['required', 'in:user,worker,business,blood_donor,doctor,hospital,car_rental,teacher,marketplace_item,property,job_post'],
            'target_id' => ['required', 'integer', 'min:1'],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'set_primary' => ['nullable'],
        ]);

        $target = $this->resolveAndAuthorizeTarget(
            $request,
            $validated['target_type'],
            (int) $validated['target_id']
        );

        if (! $target) {
            return response()->json(['message' => 'Target not found or unauthorized'], 403);
        }

        $created = [];

        DB::transaction(function () use (&$created, $request, $validated): void {
            $existingCount = MediaAsset::query()
                ->where('target_type', $validated['target_type'])
                ->where('target_id', (int) $validated['target_id'])
                ->count();

            foreach ($validated['images'] as $index => $file) {
                $relativePath = $file->store(
                    'uploads/'.$validated['target_type'].'/'.date('Y/m'),
                    'public'
                );

                $setPrimary = $request->boolean('set_primary');
                $media = MediaAsset::query()->create([
                    'user_id' => $request->user()->id,
                    'section' => $validated['section'],
                    'target_type' => $validated['target_type'],
                    'target_id' => (int) $validated['target_id'],
                    'file_path' => $relativePath,
                    'is_primary' => $setPrimary && $index === 0,
                    'sort_order' => $existingCount + $index,
                ]);

                $created[] = $this->serializeMedia($media);
            }

            if ($setPrimary && count($created) > 0) {
                MediaAsset::query()
                    ->where('target_type', $validated['target_type'])
                    ->where('target_id', (int) $validated['target_id'])
                    ->where('id', '!=', $created[0]['id'])
                    ->update(['is_primary' => false]);
            }
        });

        return response()->json([
            'message' => 'Images uploaded successfully',
            'media' => $created,
        ], 201);
    }

    public function list(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => ['required', 'in:user,worker,business,blood_donor,doctor,hospital,car_rental,teacher,marketplace_item,property,job_post'],
            'target_id' => ['required', 'integer', 'min:1'],
        ]);

        $media = MediaAsset::query()
            ->where('target_type', $validated['target_type'])
            ->where('target_id', (int) $validated['target_id'])
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MediaAsset $asset) => $this->serializeMedia($asset));

        return response()->json($media);
    }

    public function setPrimary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media_id' => ['required', 'exists:media_assets,id'],
        ]);

        $media = MediaAsset::query()->findOrFail((int) $validated['media_id']);

        $target = $this->resolveAndAuthorizeTarget($request, $media->target_type, (int) $media->target_id);
        if (! $target) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($media): void {
            MediaAsset::query()
                ->where('target_type', $media->target_type)
                ->where('target_id', $media->target_id)
                ->update(['is_primary' => false]);

            $media->update(['is_primary' => true]);
        });

        return response()->json([
            'message' => 'Primary image updated',
            'media' => $this->serializeMedia($media->fresh()),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $media = MediaAsset::query()->find($id);
        if (! $media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        $target = $this->resolveAndAuthorizeTarget($request, $media->target_type, (int) $media->target_id);
        if (! $target) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        \Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['message' => 'Media deleted']);
    }

    public function uploadProfilePhoto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();

        if ($user->photo) {
            \Storage::disk('public')->delete($user->photo);
        }

        $relativePath = $validated['image']->store(
            'uploads/profile/'.date('Y/m'),
            'public'
        );

        $user->update(['photo' => $relativePath]);

        return response()->json([
            'message' => 'Profile image uploaded',
            'photo' => $relativePath,
            'photo_url' => MediaUrl::toUrl($relativePath),
            'user' => $user->fresh(),
        ]);
    }

    private function resolveAndAuthorizeTarget(Request $request, string $targetType, int $targetId): mixed
    {
        $modelClass = $this->targetModelMap[$targetType] ?? null;
        if (! $modelClass) {
            return null;
        }

        $target = $modelClass::query()->find($targetId);
        if (! $target) {
            return null;
        }

        $user = $request->user();
        if ($user->role === 'admin') {
            return $target;
        }

        return match ($targetType) {
            'user' => $target->id === $user->id ? $target : null,
            'worker', 'business', 'blood_donor', 'doctor', 'teacher', 'marketplace_item', 'property' => (int) $target->user_id === (int) $user->id ? $target : null,
            'job_post' => (int) $target->posted_by === (int) $user->id ? $target : null,
            default => null,
        };
    }

    private function serializeMedia(MediaAsset $media): array
    {
        return [
            'id' => $media->id,
            'section' => $media->section,
            'target_type' => $media->target_type,
            'target_id' => $media->target_id,
            'file_path' => $media->file_path,
            'url' => MediaUrl::toUrl($media->file_path),
            'is_primary' => $media->is_primary,
            'sort_order' => $media->sort_order,
            'alt_text' => $media->alt_text,
            'created_at' => $media->created_at,
        ];
    }
}

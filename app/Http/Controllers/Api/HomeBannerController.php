<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeBannerController extends Controller
{
    public function active(): JsonResponse
    {
        $banners = HomeBanner::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return response()->json($this->decorateMany($banners));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = HomeBanner::query()
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->query('search').'%';
                $q->where('title', 'like', $term)->orWhere('subtitle', 'like', $term);
            });

        $banners = $query->orderBy('sort_order')->orderByDesc('id')->paginate(
            (int) min(max((int) $request->query('per_page', 20), 1), 100)
        );

        $banners->setCollection($this->decorateMany($banners->getCollection()));

        return response()->json($banners);
    }

    public function adminStore(Request $request): JsonResponse
    {
        $banner = HomeBanner::query()->create($this->validated($request));

        return response()->json([
            'message' => 'Banner created.',
            'record' => $this->decorate($banner->fresh()),
        ], 201);
    }

    public function adminUpdate(Request $request, int $id): JsonResponse
    {
        $banner = HomeBanner::query()->findOrFail($id);
        $banner->fill($this->validated($request))->save();

        return response()->json([
            'message' => 'Banner updated.',
            'record' => $this->decorate($banner->fresh()),
        ]);
    }

    public function adminDestroy(int $id): JsonResponse
    {
        HomeBanner::query()->findOrFail($id)->delete();

        return response()->json(['message' => 'Banner deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }

    private function decorateMany($banners)
    {
        $imageMap = MediaLookup::primaryUrlMap('home_banner', $banners->pluck('id')->all());

        return $banners->map(function (HomeBanner $banner) use ($imageMap): HomeBanner {
            $banner->image_url = $imageMap[$banner->id] ?? null;
            return $banner;
        });
    }

    private function decorate(HomeBanner $banner): HomeBanner
    {
        $banner->image_url = MediaLookup::primaryUrlMap('home_banner', [$banner->id])[$banner->id] ?? null;
        return $banner;
    }
}

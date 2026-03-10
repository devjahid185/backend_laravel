<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceItem;
use App\Models\MediaAsset;
use App\Support\MediaLookup;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(MarketplaceCategory::query()->orderBy('name')->get());
    }

    public function index(Request $request): JsonResponse
    {
        $items = DB::table('marketplace_items')
            ->join('marketplace_categories', 'marketplace_categories.id', '=', 'marketplace_items.category_id')
            ->join('users', 'users.id', '=', 'marketplace_items.user_id')
            ->select(
                'marketplace_items.*',
                'marketplace_categories.name as category_name',
                'users.name as seller_name',
                'users.phone as seller_phone'
            )
            ->when($request->filled('category_id'), fn ($q) => $q->where('marketplace_items.category_id', $request->integer('category_id')))
            ->where('marketplace_items.status', 'active')
            ->orderByDesc('marketplace_items.id')
            ->paginate(20);

        $itemIds = array_column($items->items(), 'id');
        $primaryMap = MediaLookup::primaryUrlMap('marketplace_item', $itemIds);
        $galleryMap = MediaAsset::query()
            ->where('target_type', 'marketplace_item')
            ->whereIn('target_id', $itemIds)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get(['target_id', 'file_path'])
            ->groupBy('target_id')
            ->map(fn ($rows) => $rows->map(fn ($row) => MediaUrl::toUrl($row->file_path))->values()->all())
            ->all();

        $items->setCollection(
            $items->getCollection()->map(function ($item) use ($primaryMap, $galleryMap) {
                $item->image_url = $primaryMap[$item->id] ?? null;
                $item->images = $galleryMap[$item->id] ?? [];

                return $item;
            })
        );

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:marketplace_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,sold,blocked'],
        ]);

        $item = MarketplaceItem::query()->create($validated + [
            'user_id' => $request->user()->id,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Item added successfully',
            'item' => $item,
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:marketplace_items,id'],
            'category_id' => ['sometimes', 'exists:marketplace_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,sold,blocked'],
        ]);

        $item = MarketplaceItem::query()
            ->where('id', $validated['item_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        unset($validated['item_id']);
        $item->update($validated);

        return response()->json([
            'message' => 'Item updated successfully',
            'item' => $item->fresh(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:marketplace_items,id'],
        ]);

        $item = MarketplaceItem::query()
            ->where('id', $validated['item_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }
}

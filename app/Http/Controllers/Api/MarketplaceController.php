<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceItem;
use App\Models\MediaAsset;
use App\Models\Report;
use App\Models\User;
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
                'users.id as seller_id',
                'users.name as seller_name',
                'users.phone as seller_phone',
                'users.district as seller_district',
                'users.upazila as seller_upazila'
            )
            ->when($request->filled('category_id'), fn ($q) => $q->where('marketplace_items.category_id', $request->integer('category_id')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('marketplace_items.price', '>=', $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('marketplace_items.price', '<=', $request->input('max_price')))
            ->when($request->filled('location'), fn ($q) => $q->where('marketplace_items.location', 'like', '%' . $request->input('location') . '%'))
            ->when($request->filled('condition'), fn ($q) => $q->where('marketplace_items.condition', $request->input('condition')))
            ->where('marketplace_items.status', 'active')
            ->when($request->input('sort') === 'price_asc', fn ($q) => $q->orderBy('marketplace_items.price'))
            ->when($request->input('sort') === 'price_desc', fn ($q) => $q->orderByDesc('marketplace_items.price'))
            ->when(! in_array($request->input('sort'), ['price_asc', 'price_desc'], true), fn ($q) => $q->orderByDesc('marketplace_items.id'))
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

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

    public function show(Request $request, int $id): JsonResponse
    {
        $item = DB::table('marketplace_items')
            ->join('marketplace_categories', 'marketplace_categories.id', '=', 'marketplace_items.category_id')
            ->join('users', 'users.id', '=', 'marketplace_items.user_id')
            ->select(
                'marketplace_items.*',
                'marketplace_categories.name as category_name',
                'users.id as seller_id',
                'users.name as seller_name',
                'users.phone as seller_phone',
                'users.district as seller_district',
                'users.upazila as seller_upazila',
                'users.address as seller_address',
                'users.photo as seller_photo'
            )
            ->where('marketplace_items.id', $id)
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        DB::table('marketplace_items')->where('id', $id)->increment('views');

        $item->image_url = MediaLookup::primaryUrlMap('marketplace_item', [$item->id])[$item->id] ?? null;
        $item->images = MediaAsset::query()
            ->where('target_type', 'marketplace_item')
            ->where('target_id', $item->id)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get(['file_path'])
            ->map(fn ($row) => MediaUrl::toUrl($row->file_path))
            ->values()
            ->all();
        $item->seller_photo_url = MediaUrl::toUrl($item->seller_photo);
        $item->is_owner = (int) $item->user_id === (int) $request->user()->id;

        return response()->json($item);
    }

    public function seller(Request $request, int $sellerId): JsonResponse
    {
        $seller = User::query()
            ->select('id', 'name', 'phone', 'district', 'upazila', 'address', 'photo')
            ->find($sellerId);

        if (! $seller) {
            return response()->json(['message' => 'Seller not found'], 404);
        }

        $items = DB::table('marketplace_items')
            ->join('marketplace_categories', 'marketplace_categories.id', '=', 'marketplace_items.category_id')
            ->select('marketplace_items.*', 'marketplace_categories.name as category_name')
            ->where('marketplace_items.user_id', $sellerId)
            ->where('marketplace_items.status', 'active')
            ->orderByDesc('marketplace_items.id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $itemIds = array_column($items->items(), 'id');
        $primaryMap = MediaLookup::primaryUrlMap('marketplace_item', $itemIds);

        $items->setCollection(
            $items->getCollection()->map(function ($item) use ($primaryMap) {
                $item->image_url = $primaryMap[$item->id] ?? null;

                return $item;
            })
        );

        $seller->photo_url = MediaUrl::toUrl($seller->photo);

        return response()->json([
            'seller' => $seller,
            'items' => $items,
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:marketplace_items,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        Report::query()->create([
            'reporter_id' => $request->user()->id,
            'target_type' => 'marketplace_item',
            'target_id' => $validated['item_id'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Report submitted']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:marketplace_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'location_lat' => ['nullable', 'numeric'],
            'location_lng' => ['nullable', 'numeric'],
            'condition' => ['nullable', 'in:new,used'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'negotiable' => ['nullable', 'boolean'],
            'delivery' => ['nullable', 'string', 'max:255'],
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
            'location_lat' => ['sometimes', 'nullable', 'numeric'],
            'location_lng' => ['sometimes', 'nullable', 'numeric'],
            'condition' => ['sometimes', 'nullable', 'in:new,used'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'negotiable' => ['sometimes', 'boolean'],
            'delivery' => ['sometimes', 'nullable', 'string', 'max:255'],
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

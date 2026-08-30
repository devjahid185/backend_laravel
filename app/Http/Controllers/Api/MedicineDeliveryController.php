<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliverySetting;
use App\Models\MedicineCart;
use App\Models\MedicineCartItem;
use App\Models\MedicineItem;
use App\Models\MedicineOrder;
use App\Models\MedicineOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicineDeliveryController extends Controller
{
    public function home(): JsonResponse
    {
        return response()->json([
            'promoted_items' => $this->decorateItems(
                MedicineItem::query()->where('is_available', true)->where('is_promoted', true)->inRandomOrder()->limit(16)->get()
            ),
            'items' => $this->decorateItems(
                MedicineItem::query()->where('is_available', true)->inRandomOrder()->limit(30)->get()
            ),
            'dosage_forms' => MedicineItem::query()->whereNotNull('dosage_form')->distinct()->orderBy('dosage_form')->limit(24)->pluck('dosage_form'),
            'companies' => MedicineItem::query()->whereNotNull('company')->distinct()->orderBy('company')->limit(24)->pluck('company'),
            'total_items' => MedicineItem::query()->where('is_available', true)->count(),
        ]);
    }

    public function items(Request $request): JsonResponse
    {
        $items = MedicineItem::query()
            ->where('is_available', true)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%' . trim((string) $request->query('q')) . '%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('brand_name', 'like', $term)
                        ->orWhere('generic_name', 'like', $term)
                        ->orWhere('company', 'like', $term)
                        ->orWhere('strength', 'like', $term);
                });
            })
            ->when($request->filled('dosage_form'), fn ($q) => $q->where('dosage_form', $request->query('dosage_form')))
            ->when($request->filled('company'), fn ($q) => $q->where('company', $request->query('company')))
            ->orderByDesc('is_promoted')
            ->orderBy('brand_name')
            ->paginate((int) min(max((int) $request->query('per_page', 30), 1), 100));

        $items->setCollection($this->decorateItems($items->getCollection()));
        return response()->json($items);
    }

    public function item(int $id): JsonResponse
    {
        return response()->json($this->decorateItem(MedicineItem::query()->findOrFail($id), true));
    }

    public function cart(Request $request): JsonResponse
    {
        $cart = $this->cartFor($request)->load('items.medicineItem');
        return response()->json($this->decorateCart($cart));
    }

    public function cartCount(Request $request): JsonResponse
    {
        $cart = $this->cartFor($request);
        return response()->json(['count' => (int) $cart->items()->sum('quantity')]);
    }

    public function addToCart(Request $request): JsonResponse
    {
        $data = $request->validate([
            'medicine_item_id' => ['required', 'exists:medicine_items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $medicine = MedicineItem::query()->where('is_available', true)->findOrFail($data['medicine_item_id']);
        $price = (float) ($medicine->unit_price ?? 0);
        $cart = $this->cartFor($request);
        $item = MedicineCartItem::query()
            ->where('medicine_cart_id', $cart->id)
            ->where('medicine_item_id', $medicine->id)
            ->first();

        $qty = (int) ($data['quantity'] ?? 1);
        if ($item) {
            $qty += (int) $item->quantity;
        } else {
            $item = new MedicineCartItem([
                'medicine_cart_id' => $cart->id,
                'medicine_item_id' => $medicine->id,
            ]);
        }

        $item->fill([
            'quantity' => $qty,
            'unit_price' => $price,
            'total_price' => round($qty * $price, 2),
            'note' => $data['note'] ?? $item->note,
        ])->save();

        return response()->json(['message' => 'Added to cart', 'cart' => $this->decorateCart($cart->fresh()->load('items.medicineItem'))]);
    }

    public function updateCartItem(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:999']]);
        $cart = $this->cartFor($request);
        $item = $cart->items()->findOrFail($id);
        $item->fill([
            'quantity' => (int) $data['quantity'],
            'total_price' => round(((int) $data['quantity']) * (float) $item->unit_price, 2),
        ])->save();

        return response()->json(['message' => 'Updated', 'cart' => $this->decorateCart($cart->fresh()->load('items.medicineItem'))]);
    }

    public function removeCartItem(Request $request, int $id): JsonResponse
    {
        $this->cartFor($request)->items()->findOrFail($id)->delete();
        return response()->json(['message' => 'Removed']);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receiver_name' => ['required', 'string', 'max:120'],
            'receiver_phone' => ['required', 'string', 'max:40'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'delivery_area' => ['nullable', 'string', 'max:120'],
            'delivery_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'order_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = $this->cartFor($request)->load('items.medicineItem');
        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $itemsTotal = round((float) $cart->items->sum('total_price'), 2);
        $deliveryFee = $this->deliveryFee();

        $order = DB::transaction(function () use ($request, $cart, $data, $itemsTotal, $deliveryFee) {
            $order = MedicineOrder::query()->create([
                'order_no' => 'MD-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'user_id' => $request->user()->id,
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'delivery_address' => $data['delivery_address'],
                'delivery_area' => $data['delivery_area'] ?? null,
                'delivery_lat' => $data['delivery_lat'] ?? null,
                'delivery_lng' => $data['delivery_lng'] ?? null,
                'delivery_map_url' => isset($data['delivery_lat'], $data['delivery_lng'])
                    ? 'https://www.google.com/maps/search/?api=1&query='.$data['delivery_lat'].','.$data['delivery_lng']
                    : null,
                'payment_method' => $data['payment_method'] ?? 'cash_on_delivery',
                'items_total' => $itemsTotal,
                'delivery_fee' => $deliveryFee,
                'grand_total' => round($itemsTotal + $deliveryFee, 2),
                'order_note' => $data['order_note'] ?? null,
            ]);

            foreach ($cart->items as $cartItem) {
                $medicine = $cartItem->medicineItem;
                MedicineOrderItem::query()->create([
                    'medicine_order_id' => $order->id,
                    'medicine_item_id' => $medicine?->id,
                    'brand_name' => $medicine?->brand_name ?? 'Medicine',
                    'generic_name' => $medicine?->generic_name,
                    'strength' => $medicine?->strength,
                    'company' => $medicine?->company,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'total_price' => $cartItem->total_price,
                ]);
            }

            $cart->items()->delete();
            return $order->load('items');
        });

        return response()->json(['message' => 'Order placed', 'order' => $order], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json(MedicineOrder::query()->with('items')->where('user_id', $request->user()->id)->latest()->paginate(20));
    }

    public function order(Request $request, int $id): JsonResponse
    {
        return response()->json(MedicineOrder::query()->with('items')->where('user_id', $request->user()->id)->findOrFail($id));
    }

    private function cartFor(Request $request): MedicineCart
    {
        return MedicineCart::query()->firstOrCreate(['user_id' => $request->user()->id]);
    }

    private function decorateCart(MedicineCart $cart): array
    {
        $cart->loadMissing('items.medicineItem');
        $itemsTotal = round((float) $cart->items->sum('total_price'), 2);
        $deliveryFee = $this->deliveryFee();
        return [
            'items' => $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'medicine_item_id' => $item->medicine_item_id,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'medicine_item' => $item->medicineItem ? $this->decorateItem($item->medicineItem) : null,
            ])->values(),
            'items_total' => $itemsTotal,
            'delivery_fee' => $deliveryFee,
            'grand_total' => round($itemsTotal + $deliveryFee, 2),
        ];
    }

    private function decorateItems($items)
    {
        return $items->map(fn (MedicineItem $item) => $this->decorateItem($item))->values();
    }

    private function decorateItem(MedicineItem $item, bool $details = false): array
    {
        $data = [
            'id' => $item->id,
            'brand_name' => $item->brand_name,
            'dosage_form' => $item->dosage_form,
            'strength' => $item->strength,
            'generic_name' => $item->generic_name,
            'company' => $item->company,
            'unit_price' => $item->unit_price === null ? null : (float) $item->unit_price,
            'price_text' => $item->price_text,
            'pack_sizes' => $item->pack_sizes,
            'image_url' => $item->image_url,
            'is_promoted' => (bool) $item->is_promoted,
            'prescription_required' => (bool) $item->prescription_required,
            'therapeutic_class' => $item->therapeutic_class,
        ];

        if ($details) {
            $data += [
                'indications' => $item->indications,
                'composition' => $item->composition,
                'dosage_and_administration' => $item->dosage_and_administration,
                'side_effects' => $item->side_effects,
                'precautions_and_warnings' => $item->precautions_and_warnings,
                'storage_conditions' => $item->storage_conditions,
            ];
        }

        return $data;
    }

    private function deliveryFee(): float
    {
        $setting = FoodDeliverySetting::current();
        if ((bool) ($setting->municipality_rule_enabled ?? false)) {
            return (float) ($setting->municipality_fixed_charge ?? $setting->fixed_charge ?? 50);
        }
        return (float) ($setting->fixed_charge ?? $setting->minimum_charge ?? 40);
    }
}

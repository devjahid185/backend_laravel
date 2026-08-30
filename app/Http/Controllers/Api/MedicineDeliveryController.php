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

    public function deliveryChargePreview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_lat' => ['required', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $cart = $this->cartFor($request)->load('items');
        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $itemsTotal = round((float) $cart->items->sum('total_price'), 2);
        $charge = $this->deliveryCharge(
            (float) $data['delivery_lat'],
            (float) $data['delivery_lng'],
            $itemsTotal,
        );

        return response()->json([
            'items_total' => $itemsTotal,
            'delivery_fee' => $charge['fee'],
            'delivery_distance_km' => $charge['distance_km'],
            'delivery_charge_mode' => $charge['mode'],
            'delivery_charge_label' => $charge['label'] ?? null,
            'grand_total' => round($itemsTotal + (float) ($charge['fee'] ?? 0), 2),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receiver_name' => ['required', 'string', 'max:120'],
            'receiver_phone' => ['required', 'string', 'max:40'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'delivery_area' => ['nullable', 'string', 'max:120'],
            'delivery_lat' => ['required', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['required', 'numeric', 'between:-180,180'],
            'delivery_map_url' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'in:cash_on_delivery,manual_bkash,manual_nagad,online'],
            'manual_transaction_id' => ['nullable', 'string', 'max:120'],
            'payment_proof_photo' => ['nullable', 'file', 'image', 'max:4096'],
            'order_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = $this->cartFor($request)->load('items.medicineItem');
        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $itemsTotal = round((float) $cart->items->sum('total_price'), 2);
        $paymentMethod = $data['payment_method'] ?? 'cash_on_delivery';
        abort_unless($this->supportsPayment($paymentMethod), 422, 'Selected payment method is not available.');
        $charge = $this->deliveryCharge(
            (float) $data['delivery_lat'],
            (float) $data['delivery_lng'],
            $itemsTotal,
        );
        $deliveryFee = (float) $charge['fee'];
        $proofPhotoPath = $request->hasFile('payment_proof_photo')
            ? $request->file('payment_proof_photo')->store('medicine/payment-proofs', 'public')
            : null;

        $order = DB::transaction(function () use ($request, $cart, $data, $itemsTotal, $deliveryFee, $paymentMethod, $proofPhotoPath, $charge) {
            $order = MedicineOrder::query()->create([
                'order_no' => 'MD-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'user_id' => $request->user()->id,
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'delivery_address' => $data['delivery_address'],
                'delivery_area' => $data['delivery_area'] ?? null,
                'delivery_lat' => $data['delivery_lat'] ?? null,
                'delivery_lng' => $data['delivery_lng'] ?? null,
                'delivery_map_url' => $data['delivery_map_url'] ?? $this->mapUrl($data['delivery_lat'], $data['delivery_lng']),
                'payment_method' => $paymentMethod,
                'manual_transaction_id' => in_array($paymentMethod, ['manual_bkash', 'manual_nagad'], true)
                    ? ($data['manual_transaction_id'] ?? null)
                    : null,
                'payment_proof_photo' => in_array($paymentMethod, ['manual_bkash', 'manual_nagad'], true)
                    ? $proofPhotoPath
                    : null,
                'items_total' => $itemsTotal,
                'delivery_fee' => $deliveryFee,
                'delivery_distance_km' => $charge['distance_km'],
                'delivery_charge_mode' => $charge['mode'],
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
            return $this->decorateOrder($order->load('items'));
        });

        return response()->json(['message' => 'Order placed', 'order' => $order], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = MedicineOrder::query()->with('items')->where('user_id', $request->user()->id)->latest()->paginate(20);
        $orders->setCollection($orders->getCollection()->map(fn (MedicineOrder $order) => $this->decorateOrder($order)));
        return response()->json($orders);
    }

    public function order(Request $request, int $id): JsonResponse
    {
        return response()->json($this->decorateOrder(
            MedicineOrder::query()->with('items')->where('user_id', $request->user()->id)->findOrFail($id)
        ));
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
            'delivery_distance_km' => null,
            'delivery_charge_mode' => 'fixed',
            'delivery_charge_label' => 'Checkout এ লোকেশন দিলে ডেলিভারি চার্জ আপডেট হবে',
            'payment_options' => $this->paymentOptions(),
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

    private function deliveryCharge(?float $lat, ?float $lng, float $itemsTotal): array
    {
        $settings = FoodDeliverySetting::current();
        if (! $settings->is_enabled) {
            return ['fee' => 0, 'distance_km' => null, 'mode' => 'disabled'];
        }

        if ($settings->free_delivery_min_order !== null && $itemsTotal >= (float) $settings->free_delivery_min_order) {
            return ['fee' => 0, 'distance_km' => null, 'mode' => 'free_delivery'];
        }

        $originLat = $settings->store_lat !== null
            ? (float) $settings->store_lat
            : ($settings->municipality_center_lat !== null ? (float) $settings->municipality_center_lat : null);
        $originLng = $settings->store_lng !== null
            ? (float) $settings->store_lng
            : ($settings->municipality_center_lng !== null ? (float) $settings->municipality_center_lng : null);
        $distanceKm = ($originLat !== null && $originLng !== null && $lat !== null && $lng !== null)
            ? $this->distanceKm($originLat, $originLng, $lat, $lng)
            : null;

        if ($distanceKm !== null && $settings->max_delivery_distance_km !== null && $distanceKm > (float) $settings->max_delivery_distance_km) {
            abort(422, 'Delivery location is outside the service area.');
        }

        if ($settings->municipality_rule_enabled && $lat !== null && $lng !== null) {
            return $this->municipalityDeliveryCharge($settings, $lat, $lng, $distanceKm);
        }

        if ($settings->charge_mode === 'fixed') {
            return ['fee' => round((float) $settings->fixed_charge, 2), 'distance_km' => $distanceKm === null ? null : round($distanceKm, 2), 'mode' => 'fixed'];
        }

        $fee = (float) $settings->base_charge + (($distanceKm ?? 0) * (float) $settings->per_km_charge);
        $fee = max((float) $settings->minimum_charge, $fee);

        return ['fee' => round($fee, 2), 'distance_km' => $distanceKm === null ? null : round($distanceKm, 2), 'mode' => 'per_km'];
    }

    private function municipalityDeliveryCharge(FoodDeliverySetting $settings, float $lat, float $lng, ?float $distanceKm): array
    {
        $fixed = (float) ($settings->municipality_fixed_charge ?? 50);
        $extraRate = (float) ($settings->municipality_extra_per_km_charge ?? 0);
        if ($this->isInsideMunicipality($settings, $lat, $lng)) {
            return [
                'fee' => round($fixed, 2),
                'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
                'mode' => 'municipality_fixed',
                'label' => 'Bhola Sadar Pourashava fixed charge',
            ];
        }

        $outsideKm = $this->municipalityOutsideDistanceKm($settings, $lat, $lng, $distanceKm);
        return [
            'fee' => round($fixed + ($outsideKm * $extraRate), 2),
            'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
            'mode' => 'municipality_outside_per_km',
            'label' => 'Pourashava outside extra per KM',
        ];
    }

    private function isInsideMunicipality(FoodDeliverySetting $settings, float $lat, float $lng): bool
    {
        $polygon = $settings->municipality_polygon ?? [];
        if (is_array($polygon) && count($polygon) >= 3) {
            return $this->pointInPolygon($lat, $lng, $polygon);
        }

        if ($settings->municipality_center_lat !== null && $settings->municipality_center_lng !== null && $settings->municipality_radius_km !== null) {
            return $this->distanceKm((float) $settings->municipality_center_lat, (float) $settings->municipality_center_lng, $lat, $lng) <= (float) $settings->municipality_radius_km;
        }

        return false;
    }

    private function municipalityOutsideDistanceKm(FoodDeliverySetting $settings, float $lat, float $lng, ?float $fallbackDistanceKm): float
    {
        if ($settings->municipality_center_lat !== null && $settings->municipality_center_lng !== null && $settings->municipality_radius_km !== null) {
            $fromCenter = $this->distanceKm((float) $settings->municipality_center_lat, (float) $settings->municipality_center_lng, $lat, $lng);
            return max(0, $fromCenter - (float) $settings->municipality_radius_km);
        }

        return max(0, (float) ($fallbackDistanceKm ?? 0));
    }

    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $latI = (float) ($polygon[$i]['lat'] ?? 0);
            $lngI = (float) ($polygon[$i]['lng'] ?? 0);
            $latJ = (float) ($polygon[$j]['lat'] ?? 0);
            $lngJ = (float) ($polygon[$j]['lng'] ?? 0);
            $intersects = (($lngI > $lng) !== ($lngJ > $lng))
                && ($lat < ($latJ - $latI) * ($lng - $lngI) / (($lngJ - $lngI) ?: 0.0000001) + $latI);
            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function mapUrl($lat, $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng;
    }

    private function paymentOptions(): array
    {
        $options = [[
            'method' => 'cash_on_delivery',
            'title' => 'Cash on Delivery',
            'subtitle' => 'মেডিসিন হাতে পেয়ে টাকা দিন',
            'number' => null,
            'instructions' => null,
        ]];

        foreach ([
            'manual_bkash' => ['title' => 'Manual bKash', 'config' => 'bkash_number'],
            'manual_nagad' => ['title' => 'Manual Nagad', 'config' => 'nagad_number'],
        ] as $method => $meta) {
            $number = trim((string) config('services.medicine_payment.'.$meta['config'], ''));
            if ($number === '') {
                continue;
            }
            $options[] = [
                'method' => $method,
                'title' => $meta['title'],
                'subtitle' => 'অর্ডার কনফার্ম করার আগে/পরে এই নম্বরে পেমেন্ট করুন',
                'number' => $number,
                'instructions' => config('services.medicine_payment.instructions') ?: 'Send Money করে transaction ID দিন।',
            ];
        }

        return $options;
    }

    private function supportsPayment(string $method): bool
    {
        return collect($this->paymentOptions())->contains('method', $method);
    }

    private function decorateOrder(MedicineOrder $order): MedicineOrder
    {
        $path = $order->payment_proof_photo;
        $order->payment_proof_photo_url = $path ? asset('storage/'.$path) : null;

        return $order;
    }
}

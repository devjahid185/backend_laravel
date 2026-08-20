<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\FoodAddress;
use App\Models\FoodBanner;
use App\Models\FoodCart;
use App\Models\FoodCartItem;
use App\Models\FoodCategory;
use App\Models\FoodCoupon;
use App\Models\FoodDeliverySetting;
use App\Models\FoodFavorite;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\FoodReview;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Models\Rider;
use App\Models\RiderOrderRequest;
use App\Services\FcmService;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FoodDeliveryController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        $popularItems = FoodItem::query()
            ->with('restaurant:id,name,address,phone')
            ->where('status', 'active')
            ->where('is_available', true)
            ->where('is_popular', true)
            ->latest()
            ->limit(16)
            ->get();

        $banners = FoodBanner::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return response()->json([
            'categories' => FoodCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'banners' => $this->decorateFoodBanners($banners, $request),
            'restaurants' => $this->restaurantQuery($request)->limit(12)->get()->map(fn ($r) => $this->decorateRestaurant($r, $request)),
            'popular_items' => $this->decorateFoodItems($popularItems),
            'offers' => FoodCoupon::query()->where('is_active', true)->latest()->limit(10)->get(),
            'areas' => ['Bhola Sadar', 'Borhanuddin', 'Daulatkhan', 'Lalmohan', 'Char Fasson', 'Tazumuddin', 'Manpura', 'Ilisha', 'Bangla Bazar', 'Ukil Para', 'Sadar Road', 'Notun Bazar', 'Launch Ghat'],
            'order_statuses' => $this->statusLabels(),
        ]);
    }

    public function restaurants(Request $request): JsonResponse
    {
        $restaurants = $this->restaurantQuery($request)
            ->paginate((int) min(max((int) $request->query('per_page', 20), 1), 100));
        $restaurants->setCollection($restaurants->getCollection()->map(fn ($r) => $this->decorateRestaurant($r, $request)));
        return response()->json($restaurants);
    }

    public function restaurant(Request $request, int $id): JsonResponse
    {
        $restaurant = Restaurant::query()->findOrFail($id);
        $restaurant->increment('views');
        $restaurant = $this->decorateRestaurant($restaurant, $request);
        $items = FoodItem::query()
            ->where('restaurant_id', $id)
            ->where('status', 'active')
            ->orderByDesc('is_popular')
            ->orderBy('name')
            ->get();
        $restaurant->menu_categories = FoodCategory::query()
            ->whereIn('id', $items->pluck('food_category_id')->filter()->unique())
            ->orderBy('sort_order')
            ->get();
        $restaurant->menu_items = $this->decorateFoodItems($items);
        $restaurant->reviews = $this->foodReviewQuery()
            ->where('restaurant_id', $id)
            ->whereNull('food_item_id')
            ->latest()
            ->limit(20)
            ->get();
        return response()->json($restaurant);
    }

    public function items(Request $request): JsonResponse
    {
        $items = FoodItem::query()
            ->with('restaurant:id,name,address,phone,opening_hours,status,delivery_available,accepts_food_orders')
            ->where('status', 'active')
            ->where('is_available', true)
            ->whereHas('restaurant', function ($query) use ($request): void {
                $query->where('status', 'active')
                    ->where('delivery_available', true)
                    ->where(function ($q): void {
                        $q->whereNull('accepts_food_orders')->orWhere('accepts_food_orders', true);
                    })
                    ->when($request->filled('area'), function ($q) use ($request): void {
                        $term = '%' . $request->query('area') . '%';
                        $q->where(function ($sub) use ($term): void {
                            $sub->where('address', 'like', $term)->orWhere('upazila', 'like', $term);
                        });
                    });
            })
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%' . $request->query('q') . '%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('restaurant', fn ($r) => $r->where('name', 'like', $term));
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('food_category_id', (int) $request->query('category_id')))
            ->orderByDesc('is_popular')
            ->latest()
            ->paginate((int) min(max((int) $request->query('per_page', 30), 1), 100));

        $items->setCollection($this->decorateFoodItems($items->getCollection()));
        return response()->json($items);
    }

    public function item(int $id): JsonResponse
    {
        $item = $this->decorateFoodItem(FoodItem::query()->with('restaurant:id,name,address,phone,opening_hours')->findOrFail($id));
        $item->reviews = $this->foodReviewQuery()
            ->where('food_item_id', $id)
            ->latest()
            ->limit(20)
            ->get();
        return response()->json($item);
    }

    public function ownerDashboard(Request $request): JsonResponse
    {
        $restaurantIds = Restaurant::query()->where('user_id', $request->user()->id)->pluck('id');
        $orders = FoodOrder::query()->whereIn('restaurant_id', $restaurantIds);

        return response()->json([
            'restaurants' => Restaurant::query()->whereIn('id', $restaurantIds)->latest()->get()->map(fn ($r) => $this->decorateRestaurantForOwner($r)),
            'stats' => [
                'restaurants' => $restaurantIds->count(),
                'menu_items' => FoodItem::query()->whereIn('restaurant_id', $restaurantIds)->count(),
                'pending_orders' => (clone $orders)->where('status', 'pending')->count(),
                'active_orders' => (clone $orders)->whereIn('status', ['accepted', 'preparing', 'picked_up', 'on_the_way'])->count(),
                'completed_orders' => (clone $orders)->where('status', 'delivered')->count(),
                'sales_total' => (float) (clone $orders)->where('status', 'delivered')->sum('grand_total'),
            ],
            'recent_orders' => FoodOrder::query()->with('items', 'restaurant:id,name,lat,lng', 'rider:id,name,phone,last_lat,last_lng,last_location_at')->whereIn('restaurant_id', $restaurantIds)->latest()->limit(10)->get(),
            'recent_reviews' => $this->foodReviewQuery()
                ->whereIn('restaurant_id', $restaurantIds)
                ->latest()
                ->limit(12)
                ->get(),
        ]);
    }

    public function ownerRestaurants(Request $request): JsonResponse
    {
        $restaurants = Restaurant::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($r) => $this->decorateRestaurantForOwner($r));

        return response()->json($restaurants);
    }

    public function saveOwnerRestaurant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'exists:restaurant_categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'average_prep_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'cuisines' => ['nullable', 'array'],
            'features' => ['nullable', 'array'],
            'delivery_available' => ['nullable', 'boolean'],
            'takeaway_available' => ['nullable', 'boolean'],
            'dine_in_available' => ['nullable', 'boolean'],
            'accepts_food_orders' => ['nullable', 'boolean'],
            'cod_enabled' => ['nullable', 'boolean'],
            'manual_bkash_number' => ['nullable', 'string', 'max:40'],
            'manual_nagad_number' => ['nullable', 'string', 'max:40'],
            'manual_payment_instructions' => ['nullable', 'string', 'max:1000'],
            'service_radius_km' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'description' => ['nullable', 'string'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if (! empty($data['id'])) {
            $restaurant = Restaurant::query()->where('user_id', $request->user()->id)->findOrFail($data['id']);
            unset($data['id']);
            $restaurant->fill($data)->save();
        } else {
            unset($data['id']);
            $restaurant = Restaurant::query()->create($data + [
                'user_id' => $request->user()->id,
                'district' => $data['district'] ?? 'Bhola',
                'delivery_available' => $data['delivery_available'] ?? true,
                'accepts_food_orders' => $data['accepts_food_orders'] ?? true,
                'cod_enabled' => $data['cod_enabled'] ?? true,
                'takeaway_available' => $data['takeaway_available'] ?? true,
                'dine_in_available' => $data['dine_in_available'] ?? true,
                'status' => 'pending',
            ]);
        }

        if ($restaurant->lat !== null && $restaurant->lng !== null) {
            FoodOrder::query()
                ->with('restaurant')
                ->where('restaurant_id', $restaurant->id)
                ->whereNull('rider_id')
                ->whereIn('status', ['accepted', 'preparing'])
                ->latest()
                ->limit(20)
                ->get()
                ->each(fn (FoodOrder $order) => $this->dispatchOrderToNearbyRiders($order));
        }

        return response()->json([
            'message' => $restaurant->status === 'active' ? 'Restaurant saved.' : 'Restaurant submitted for admin approval.',
            'restaurant' => $this->decorateRestaurantForOwner($restaurant->fresh()),
        ]);
    }

    public function ownerItems(Request $request): JsonResponse
    {
        $restaurantIds = Restaurant::query()->where('user_id', $request->user()->id)->pluck('id');
        $items = FoodItem::query()->with('category:id,name', 'restaurant:id,name')->whereIn('restaurant_id', $restaurantIds)->latest()->paginate(50);
        $items->setCollection($this->decorateFoodItems($items->getCollection()));
        return response()->json($items);
    }

    public function saveOwnerItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'restaurant_id' => ['required', 'exists:restaurants,id'],
            'food_category_id' => ['nullable', 'exists:food_categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'preparation_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'size_options' => ['nullable', 'array'],
            'spice_options' => ['nullable', 'array'],
            'add_ons' => ['nullable', 'array'],
            'is_available' => ['nullable', 'boolean'],
            'is_popular' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:active,pending,inactive'],
        ]);

        $this->ownedRestaurant($request, (int) $data['restaurant_id']);
        $payload = $data + ['status' => 'active', 'is_available' => true, 'preparation_minutes' => 20];
        if (empty($payload['slug'])) {
            $payload['slug'] = Str::slug($payload['name']) ?: 'food-item';
        }

        if (! empty($data['id'])) {
            $item = FoodItem::query()->where('restaurant_id', $data['restaurant_id'])->findOrFail($data['id']);
            unset($payload['id']);
            $item->fill($payload)->save();
        } else {
            unset($payload['id']);
            $item = FoodItem::query()->create($payload);
        }

        return response()->json(['message' => 'Menu item saved.', 'item' => $this->decorateFoodItem($item->fresh('category'))]);
    }

    public function deleteOwnerItem(Request $request, int $id): JsonResponse
    {
        $restaurantIds = Restaurant::query()->where('user_id', $request->user()->id)->pluck('id');
        FoodItem::query()->whereIn('restaurant_id', $restaurantIds)->findOrFail($id)->delete();
        return response()->json(['message' => 'Menu item deleted.']);
    }

    public function ownerOrders(Request $request): JsonResponse
    {
        $restaurantIds = Restaurant::query()->where('user_id', $request->user()->id)->pluck('id');
        return response()->json(FoodOrder::query()
            ->with('items', 'restaurant:id,name,phone,address,lat,lng', 'rider:id,name,phone,last_lat,last_lng,last_location_at')
            ->whereIn('restaurant_id', $restaurantIds)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(50));
    }

    public function addresses(Request $request): JsonResponse
    {
        return response()->json(FoodAddress::query()->where('user_id', $request->user()->id)->latest('is_default')->latest()->get());
    }

    public function saveAddress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'label' => ['nullable', 'string', 'max:60'],
            'receiver_name' => ['required', 'string', 'max:120'],
            'receiver_phone' => ['required', 'string', 'max:40'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'area' => ['nullable', 'string', 'max:120'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'address' => ['required', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $data['user_id'] = $request->user()->id;
        $data['district'] = $data['district'] ?? 'Bhola';
        $data['label'] = $data['label'] ?? 'Home';
        if (! empty($data['is_default'])) {
            FoodAddress::query()->where('user_id', $request->user()->id)->update(['is_default' => false]);
        }
        if (! empty($data['id'])) {
            $address = FoodAddress::query()->where('user_id', $request->user()->id)->findOrFail($data['id']);
            $address->fill($data)->save();
        } else {
            unset($data['id']);
            $address = FoodAddress::query()->create($data);
        }
        return response()->json(['message' => 'Address saved', 'address' => $address]);
    }

    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        FoodAddress::query()->where('user_id', $request->user()->id)->where('id', $id)->delete();
        return response()->json(['message' => 'Address deleted']);
    }

    public function cart(Request $request): JsonResponse
    {
        return response()->json($this->cartPayload($request->user()->id));
    }

    public function cartCount(Request $request): JsonResponse
    {
        $cart = FoodCart::query()->where('user_id', $request->user()->id)->first();
        $count = $cart
            ? (int) FoodCartItem::query()->where('food_cart_id', $cart->id)->sum('quantity')
            : 0;

        return response()->json(['count' => $count]);
    }

    public function addToCart(Request $request): JsonResponse
    {
        $data = $request->validate([
            'food_item_id' => ['required', 'exists:food_items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'size' => ['nullable', 'string', 'max:80'],
            'spice_level' => ['nullable', 'string', 'max:80'],
            'add_ons' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $item = FoodItem::query()->findOrFail($data['food_item_id']);
        abort_unless($item->is_available && $item->status === 'active', 422, 'Item is not available.');
        $cart = FoodCart::query()->firstOrCreate(['user_id' => $request->user()->id]);
        if ($cart->restaurant_id && (int) $cart->restaurant_id !== (int) $item->restaurant_id) {
            $cart->items()->delete();
        }
        $cart->restaurant_id = $item->restaurant_id;
        $cart->save();

        $quantity = (int) ($data['quantity'] ?? 1);
        $unit = (float) ($item->discount_price ?: $item->price);
        FoodCartItem::query()->create([
            'food_cart_id' => $cart->id,
            'food_item_id' => $item->id,
            'quantity' => $quantity,
            'size' => $data['size'] ?? null,
            'spice_level' => $data['spice_level'] ?? null,
            'add_ons' => $data['add_ons'] ?? [],
            'note' => $data['note'] ?? null,
            'unit_price' => $unit,
            'total_price' => $unit * $quantity,
        ]);
        return response()->json($this->cartPayload($request->user()->id), 201);
    }

    public function updateCartItem(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:50']]);
        $row = FoodCartItem::query()->whereHas('foodItem')->findOrFail($id);
        $cart = FoodCart::query()->where('user_id', $request->user()->id)->findOrFail($row->food_cart_id);
        $row->quantity = (int) $data['quantity'];
        $row->total_price = (float) $row->unit_price * $row->quantity;
        $row->save();
        return response()->json($this->cartPayload($cart->user_id));
    }

    public function removeCartItem(Request $request, int $id): JsonResponse
    {
        $cart = FoodCart::query()->where('user_id', $request->user()->id)->first();
        if ($cart) {
            FoodCartItem::query()->where('food_cart_id', $cart->id)->where('id', $id)->delete();
        }
        return response()->json($this->cartPayload($request->user()->id));
    }

    public function clearCart(Request $request): JsonResponse
    {
        $cart = FoodCart::query()->where('user_id', $request->user()->id)->first();
        $cart?->items()->delete();
        return response()->json($this->cartPayload($request->user()->id));
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'food_address_id' => ['nullable', 'exists:food_addresses,id'],
            'order_type' => ['nullable', 'in:delivery,pickup'],
            'payment_method' => ['nullable', 'in:cash_on_delivery,manual_bkash,manual_nagad,online'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'order_note' => ['nullable', 'string', 'max:500'],
            'delivery_lat' => ['required_if:order_type,delivery', 'nullable', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['required_if:order_type,delivery', 'nullable', 'numeric', 'between:-180,180'],
            'delivery_map_url' => ['nullable', 'string', 'max:255'],
        ]);
        $cart = FoodCart::query()->with(['items.foodItem', 'restaurant'])->where('user_id', $request->user()->id)->firstOrFail();
        abort_if($cart->items->isEmpty(), 422, 'Cart is empty.');
        $paymentMethod = $data['payment_method'] ?? $this->defaultPaymentMethod($cart->restaurant);
        abort_unless($this->restaurantSupportsPayment($cart->restaurant, $paymentMethod), 422, 'Selected payment method is not available for this restaurant.');
        $address = null;
        if (($data['order_type'] ?? 'delivery') === 'delivery') {
            $address = ! empty($data['food_address_id'])
                ? FoodAddress::query()->where('user_id', $request->user()->id)->findOrFail($data['food_address_id'])
                : FoodAddress::query()->where('user_id', $request->user()->id)->where('is_default', true)->first();
            abort_unless($address, 422, 'Delivery address is required.');
            abort_unless(isset($data['delivery_lat'], $data['delivery_lng']), 422, 'Current delivery location is required.');
        }

        $itemsTotal = (float) $cart->items->sum('total_price');
        $charge = ($data['order_type'] ?? 'delivery') === 'delivery'
            ? $this->deliveryCharge($cart->restaurant, $address?->area, (float) $data['delivery_lat'], (float) $data['delivery_lng'], $itemsTotal)
            : ['fee' => 0, 'distance_km' => null, 'mode' => 'pickup'];
        if (($data['order_type'] ?? 'delivery') === 'delivery' && $charge['distance_km'] === null) {
            $charge['distance_km'] = $this->orderRouteDistance($cart->restaurant, (float) $data['delivery_lat'], (float) $data['delivery_lng']);
        }
        $deliveryFee = $charge['fee'];
        [$discount, $coupon] = $this->couponDiscount($data['coupon_code'] ?? null, $itemsTotal, $deliveryFee, $cart->restaurant_id);
        $grand = max(0, $itemsTotal + $deliveryFee - $discount);

        $order = DB::transaction(function () use ($request, $cart, $address, $data, $itemsTotal, $deliveryFee, $discount, $coupon, $grand, $charge, $paymentMethod) {
            $order = FoodOrder::query()->create([
                'order_no' => 'FD-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'user_id' => $request->user()->id,
                'restaurant_id' => $cart->restaurant_id,
                'food_address_id' => $address?->id,
                'receiver_name' => $address?->receiver_name ?? $request->user()->name,
                'receiver_phone' => $address?->receiver_phone ?? ($request->user()->phone ?? ''),
                'delivery_address' => $address?->address ?? 'Pickup from restaurant',
                'delivery_area' => $address?->area,
                'landmark' => $address?->landmark,
                'delivery_lat' => $data['delivery_lat'] ?? $address?->lat,
                'delivery_lng' => $data['delivery_lng'] ?? $address?->lng,
                'delivery_map_url' => $data['delivery_map_url'] ?? $this->mapUrl($data['delivery_lat'] ?? $address?->lat, $data['delivery_lng'] ?? $address?->lng),
                'order_type' => $data['order_type'] ?? 'delivery',
                'payment_method' => $paymentMethod,
                'items_total' => $itemsTotal,
                'delivery_fee' => $deliveryFee,
                'delivery_distance_km' => $charge['distance_km'],
                'delivery_charge_mode' => $charge['mode'],
                'discount_amount' => $discount,
                'grand_total' => $grand,
                'coupon_code' => $coupon?->code,
                'order_note' => $data['order_note'] ?? null,
                'estimated_delivery_at' => now()->addMinutes(45),
            ]);
            foreach ($cart->items as $row) {
                $imageUrl = MediaLookup::primaryUrlMap('food_item', [(int) $row->food_item_id])[(int) $row->food_item_id] ?? null;
                $itemPayload = [
                    'food_order_id' => $order->id,
                    'food_item_id' => $row->food_item_id,
                    'name' => $row->foodItem?->name ?? 'Food item',
                    'quantity' => $row->quantity,
                    'size' => $row->size,
                    'spice_level' => $row->spice_level,
                    'add_ons' => $row->add_ons ?? [],
                    'note' => $row->note,
                    'unit_price' => $row->unit_price,
                    'total_price' => $row->total_price,
                ];
                if (Schema::hasColumn('food_order_items', 'image_url')) {
                    $itemPayload['image_url'] = $imageUrl;
                }
                FoodOrderItem::query()->create($itemPayload);
            }
            $cart->items()->delete();
            return $order->load('items', 'restaurant:id,name,phone,address,lat,lng');
        });
        $this->notifyRestaurantOwner($order);
        return response()->json(['message' => 'Order placed', 'order' => $order], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json(FoodOrder::query()->with('restaurant:id,name,phone,address,lat,lng', 'rider:id,name,phone,last_lat,last_lng,last_location_at')->where('user_id', $request->user()->id)->latest()->paginate(20));
    }

    public function order(Request $request, int $id): JsonResponse
    {
        $order = FoodOrder::query()
            ->with('items', 'restaurant:id,name,phone,address,opening_hours,lat,lng', 'rider:id,name,phone,last_lat,last_lng,last_location_at')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        $order->review = $this->foodReviewQuery()
            ->where('user_id', $request->user()->id)
            ->where('food_order_id', $order->id)
            ->first();

        return response()->json($order);
    }

    public function cancelOrder(Request $request, int $id): JsonResponse
    {
        $order = FoodOrder::query()->where('user_id', $request->user()->id)->findOrFail($id);
        abort_unless(in_array($order->status, ['pending', 'accepted'], true), 422, 'This order cannot be cancelled now.');
        $order->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Order cancelled', 'order' => $order]);
    }

    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,accepted,preparing,picked_up,on_the_way,delivered,cancelled,rejected'],
            'delivery_person_name' => ['nullable', 'string', 'max:120'],
            'delivery_person_phone' => ['nullable', 'string', 'max:40'],
        ]);
        $order = FoodOrder::query()->with('restaurant')->findOrFail($id);
        abort_unless((int) $order->restaurant?->user_id === (int) $request->user()->id, 403, 'Not allowed.');
        $order->fill($data);
        if ($data['status'] === 'accepted') $order->accepted_at = now();
        if ($data['status'] === 'delivered') $order->delivered_at = now();
        $order->save();
        if (in_array($data['status'], ['accepted', 'preparing'], true)) {
            $this->dispatchOrderToNearbyRiders($order->fresh('restaurant'));
        }
        $this->notifyFoodOrderCustomer($order->fresh('restaurant'));
        return response()->json(['message' => 'Order updated', 'order' => $order]);
    }

    public function favorites(Request $request): JsonResponse
    {
        return response()->json(FoodFavorite::query()->where('user_id', $request->user()->id)->latest()->get());
    }

    public function toggleFavorite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'restaurant_id' => ['nullable', 'exists:restaurants,id'],
            'food_item_id' => ['nullable', 'exists:food_items,id'],
        ]);
        $query = FoodFavorite::query()->where('user_id', $request->user()->id)
            ->where('restaurant_id', $data['restaurant_id'] ?? null)
            ->where('food_item_id', $data['food_item_id'] ?? null);
        if ($query->exists()) {
            $query->delete();
            return response()->json(['message' => 'Removed from favorites', 'favorite' => false]);
        }
        $favorite = FoodFavorite::query()->create($data + ['user_id' => $request->user()->id]);
        return response()->json(['message' => 'Added to favorites', 'favorite' => true, 'record' => $favorite]);
    }

    public function review(Request $request): JsonResponse
    {
        $data = $request->validate([
            'restaurant_id' => ['nullable', 'exists:restaurants,id'],
            'food_item_id' => ['nullable', 'exists:food_items,id'],
            'food_order_id' => ['required', 'exists:food_orders,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'string', 'max:255'],
        ]);

        $order = FoodOrder::query()
            ->with('items:id,food_order_id,food_item_id')
            ->where('id', (int) $data['food_order_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->status !== 'delivered') {
            return response()->json(['message' => 'Review can be submitted after the order is delivered.'], 422);
        }

        if (FoodReview::query()->where('user_id', $request->user()->id)->where('food_order_id', $order->id)->exists()) {
            return response()->json(['message' => 'You have already reviewed this order.'], 422);
        }

        if (! empty($data['food_item_id'])) {
            $belongsToOrder = $order->items->contains(fn ($item) => (int) $item->food_item_id === (int) $data['food_item_id']);
            if (! $belongsToOrder) {
                return response()->json(['message' => 'This food item is not part of this order.'], 422);
            }
            $data['restaurant_id'] = $order->restaurant_id;
        } else {
            $data['restaurant_id'] = $order->restaurant_id;
            $data['food_item_id'] = null;
        }

        $review = FoodReview::query()->create($data + ['user_id' => $request->user()->id, 'is_verified_order' => true, 'status' => 'active']);
        $this->refreshRating($data['restaurant_id'] ?? null, $data['food_item_id'] ?? null);
        return response()->json(['message' => 'Review saved', 'review' => $review->load('user:id,name', 'ownerReplyUser:id,name')], 201);
    }

    public function reviews(Request $request): JsonResponse
    {
        $data = $request->validate([
            'restaurant_id' => ['nullable', 'exists:restaurants,id'],
            'food_item_id' => ['nullable', 'exists:food_items,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (empty($data['restaurant_id']) && empty($data['food_item_id'])) {
            return response()->json(['message' => 'Restaurant or food item is required.'], 422);
        }

        $reviews = $this->foodReviewQuery()
            ->when(! empty($data['restaurant_id']), fn ($q) => $q->where('restaurant_id', (int) $data['restaurant_id']))
            ->when(! empty($data['food_item_id']), fn ($q) => $q->where('food_item_id', (int) $data['food_item_id']))
            ->latest()
            ->paginate((int) ($data['per_page'] ?? 20));

        return response()->json($reviews);
    }

    public function ownerReviews(Request $request): JsonResponse
    {
        $restaurantIds = Restaurant::query()->where('user_id', $request->user()->id)->pluck('id');
        $reviews = $this->foodReviewQuery()
            ->whereIn('restaurant_id', $restaurantIds)
            ->latest()
            ->paginate((int) min(max((int) $request->query('per_page', 20), 1), 50));

        return response()->json($reviews);
    }

    public function ownerReplyReview(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'owner_reply' => ['required', 'string', 'min:2', 'max:1000'],
        ]);

        $restaurantIds = Restaurant::query()->where('user_id', $request->user()->id)->pluck('id');
        $review = FoodReview::query()->whereIn('restaurant_id', $restaurantIds)->findOrFail($id);
        $review->forceFill([
            'owner_reply' => $data['owner_reply'],
            'owner_reply_user_id' => $request->user()->id,
            'owner_replied_at' => now(),
        ])->save();

        return response()->json(['message' => 'Reply saved', 'review' => $review->load('user:id,name', 'ownerReplyUser:id,name', 'foodItem:id,name')]);
    }

    private function restaurantQuery(Request $request)
    {
        return Restaurant::query()
            ->where('status', 'active')
            ->where('delivery_available', true)
            ->where(function ($q) {
                $q->whereNull('accepts_food_orders')->orWhere('accepts_food_orders', true);
            })
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->query('q') . '%'))
            ->when($request->filled('area'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%' . $request->query('area') . '%';
                $sub->where('address', 'like', $term)->orWhere('upazila', 'like', $term);
            }))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->query('category_id')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('min_price', '>=', (int) $request->query('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('max_price', '<=', (int) $request->query('max_price')))
            ->orderByDesc('rating')
            ->orderByDesc('id');
    }

    private function foodReviewQuery()
    {
        return FoodReview::query()
            ->with('user:id,name', 'ownerReplyUser:id,name', 'foodItem:id,name')
            ->when(Schema::hasColumn('food_reviews', 'status'), fn ($q) => $q->where('status', 'active'));
    }

    private function decorateRestaurant(Restaurant $restaurant, Request $request): Restaurant
    {
        $restaurant->image_url = MediaLookup::primaryUrlMap('restaurant', [$restaurant->id])[$restaurant->id] ?? null;
        $restaurant->delivery_time = '৩০-৫০ মিনিট';
        $restaurant->minimum_order = $restaurant->min_price ?: null;
        $restaurant->is_open = true;
        $restaurant->payment_options = $this->restaurantPaymentOptions($restaurant);
        return $restaurant;
    }

    private function decorateRestaurantForOwner(Restaurant $restaurant): Restaurant
    {
        $restaurant->image_url = MediaLookup::primaryUrlMap('restaurant', [$restaurant->id])[$restaurant->id] ?? null;
        $restaurant->category_name = $restaurant->category_id
            ? RestaurantCategory::query()->find($restaurant->category_id)?->name
            : null;
        $restaurant->menu_items_count = FoodItem::query()->where('restaurant_id', $restaurant->id)->count();
        $restaurant->pending_orders_count = FoodOrder::query()->where('restaurant_id', $restaurant->id)->where('status', 'pending')->count();
        $restaurant->payment_options = $this->restaurantPaymentOptions($restaurant);
        return $restaurant;
    }

    private function restaurantPaymentOptions(?Restaurant $restaurant): array
    {
        if (! $restaurant) {
            return [];
        }

        $options = [];
        if ($restaurant->cod_enabled ?? true) {
            $options[] = [
                'method' => 'cash_on_delivery',
                'title' => 'Cash on Delivery',
                'subtitle' => 'খাবার হাতে পেয়ে টাকা দিন',
                'number' => null,
                'instructions' => null,
            ];
        }

        foreach ([
            'manual_bkash' => ['title' => 'Manual bKash', 'field' => 'manual_bkash_number'],
            'manual_nagad' => ['title' => 'Manual Nagad', 'field' => 'manual_nagad_number'],
        ] as $method => $config) {
            $number = trim((string) ($restaurant->{$config['field']} ?? ''));
            if ($number === '') {
                continue;
            }
            $options[] = [
                'method' => $method,
                'title' => $config['title'],
                'subtitle' => 'অর্ডার করার আগে/পরে এই নম্বরে পেমেন্ট করুন',
                'number' => $number,
                'instructions' => $restaurant->manual_payment_instructions,
            ];
        }

        return $options;
    }

    private function restaurantSupportsPayment(?Restaurant $restaurant, string $method): bool
    {
        return collect($this->restaurantPaymentOptions($restaurant))->contains('method', $method);
    }

    private function defaultPaymentMethod(?Restaurant $restaurant): string
    {
        return $this->restaurantPaymentOptions($restaurant)[0]['method'] ?? 'cash_on_delivery';
    }

    private function decorateFoodItem(FoodItem $item): FoodItem
    {
        $item->image_url = MediaLookup::primaryUrlMap('food_item', [$item->id])[$item->id] ?? null;
        return $item;
    }

    private function decorateFoodItems($items)
    {
        $imageMap = MediaLookup::primaryUrlMap('food_item', $items->pluck('id')->all());
        return $items->map(function (FoodItem $item) use ($imageMap) {
            $item->image_url = $imageMap[$item->id] ?? null;
            return $item;
        });
    }

    private function decorateFoodBanners($banners, Request $request)
    {
        $imageMap = MediaLookup::primaryUrlMap('food_banner', $banners->pluck('id')->all());

        return $banners->map(function (FoodBanner $banner) use ($imageMap, $request): FoodBanner {
            $banner->image_url = $imageMap[$banner->id] ?? $this->normalizePublicUrl($banner->image_url, $request);
            return $banner;
        });
    }

    private function normalizePublicUrl(?string $url, Request $request): ?string
    {
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, 'http://localhost') || str_starts_with($url, 'http://127.0.0.1')) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path) {
                return rtrim($request->getSchemeAndHttpHost(), '/').$path;
            }
        }

        return $url;
    }

    private function ownedRestaurant(Request $request, int $restaurantId): Restaurant
    {
        return Restaurant::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($restaurantId);
    }

    private function cartPayload(int $userId): array
    {
        $cart = FoodCart::query()->with(['items.foodItem', 'restaurant'])->firstOrCreate(['user_id' => $userId]);
        $imageMap = MediaLookup::primaryUrlMap('food_item', $cart->items->pluck('food_item_id')->all());
        $items = $cart->items->map(function ($row) use ($imageMap) {
            return [
                'id' => $row->id,
                'food_item_id' => $row->food_item_id,
                'name' => $row->foodItem?->name,
                'image_url' => $imageMap[$row->food_item_id] ?? null,
                'quantity' => $row->quantity,
                'size' => $row->size,
                'spice_level' => $row->spice_level,
                'add_ons' => $row->add_ons ?? [],
                'note' => $row->note,
                'unit_price' => (float) $row->unit_price,
                'total_price' => (float) $row->total_price,
            ];
        })->values();
        $itemsTotal = (float) $cart->items->sum('total_price');
        $deliveryFee = $cart->restaurant_id ? $this->deliveryFee($cart->restaurant, null) : 0;
        return [
            'cart' => $cart,
            'restaurant' => $cart->restaurant,
            'payment_options' => $this->restaurantPaymentOptions($cart->restaurant),
            'items' => $items,
            'items_total' => $itemsTotal,
            'delivery_fee' => $deliveryFee,
            'grand_total' => $itemsTotal + $deliveryFee,
        ];
    }

    private function deliveryFee(?Restaurant $restaurant, ?string $area): float
    {
        return $this->deliveryCharge($restaurant, $area, null, null, 0)['fee'];
    }

    private function deliveryCharge(?Restaurant $restaurant, ?string $area, ?float $lat, ?float $lng, float $itemsTotal): array
    {
        if (! $restaurant?->delivery_available) {
            return ['fee' => 0, 'distance_km' => null, 'mode' => 'disabled'];
        }

        $settings = FoodDeliverySetting::current();
        if (! $settings->is_enabled) {
            return ['fee' => 0, 'distance_km' => null, 'mode' => 'disabled'];
        }

        if ($settings->free_delivery_min_order !== null && $itemsTotal >= (float) $settings->free_delivery_min_order) {
            return ['fee' => 0, 'distance_km' => null, 'mode' => 'free_delivery'];
        }

        if ($settings->charge_mode === 'fixed') {
            return ['fee' => round((float) $settings->fixed_charge, 2), 'distance_km' => null, 'mode' => 'fixed'];
        }

        $originLat = $restaurant->lat !== null ? (float) $restaurant->lat : ($settings->store_lat !== null ? (float) $settings->store_lat : null);
        $originLng = $restaurant->lng !== null ? (float) $restaurant->lng : ($settings->store_lng !== null ? (float) $settings->store_lng : null);
        $distanceKm = ($originLat !== null && $originLng !== null && $lat !== null && $lng !== null)
            ? $this->distanceKm($originLat, $originLng, $lat, $lng)
            : null;

        if ($distanceKm !== null && $settings->max_delivery_distance_km !== null && $distanceKm > (float) $settings->max_delivery_distance_km) {
            abort(422, 'Delivery location is outside the service area.');
        }

        $fee = (float) $settings->base_charge + (($distanceKm ?? 0) * (float) $settings->per_km_charge);
        $fee = max((float) $settings->minimum_charge, $fee);

        return ['fee' => round($fee, 2), 'distance_km' => $distanceKm === null ? null : round($distanceKm, 2), 'mode' => 'per_km'];
    }

    private function orderRouteDistance(?Restaurant $restaurant, ?float $lat, ?float $lng): ?float
    {
        if ($restaurant?->lat === null || $restaurant?->lng === null || $lat === null || $lng === null) {
            return null;
        }

        return round($this->distanceKm((float) $restaurant->lat, (float) $restaurant->lng, $lat, $lng), 2);
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

    private function couponDiscount(?string $code, float $itemsTotal, float $deliveryFee, int $restaurantId): array
    {
        if (! $code) return [0, null];
        $coupon = FoodCoupon::query()->where('code', strtoupper($code))->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('restaurant_id')->orWhere('restaurant_id', $restaurantId))->first();
        if (! $coupon || $itemsTotal < (float) $coupon->minimum_order) return [0, null];
        $discount = match ($coupon->discount_type) {
            'percent' => $itemsTotal * ((float) $coupon->discount_value / 100),
            'free_delivery' => $deliveryFee,
            default => (float) $coupon->discount_value,
        };
        return [min($discount, $itemsTotal + $deliveryFee), $coupon];
    }

    private function statusLabels(): array
    {
        return [
            'pending' => 'অর্ডার পাঠানো হয়েছে',
            'accepted' => 'রেস্টুরেন্ট অর্ডার গ্রহণ করেছে',
            'preparing' => 'খাবার প্রস্তুত হচ্ছে',
            'picked_up' => 'ডেলিভারি পারসন খাবার নিয়েছে',
            'on_the_way' => 'খাবার পথে আছে',
            'delivered' => 'ডেলিভারি সম্পন্ন',
            'cancelled' => 'অর্ডার বাতিল',
            'rejected' => 'রেস্টুরেন্ট অর্ডার গ্রহণ করেনি',
        ];
    }
    private function refreshRating(?int $restaurantId, ?int $itemId): void
    {
        if ($restaurantId) {
            Restaurant::query()->where('id', $restaurantId)->update(['rating' => FoodReview::query()->where('restaurant_id', $restaurantId)->avg('rating') ?: 0]);
        }
        if ($itemId) {
            FoodItem::query()->where('id', $itemId)->update([
                'rating' => FoodReview::query()->where('food_item_id', $itemId)->avg('rating') ?: 0,
                'reviews_count' => FoodReview::query()->where('food_item_id', $itemId)->count(),
            ]);
        }
    }

    private function notifyRestaurantOwner(FoodOrder $order): void
    {
        $ownerId = $order->restaurant?->user_id;
        if (! $ownerId) return;

        $restaurantName = $order->restaurant?->name ?: 'আপনার রেস্টুরেন্ট';
        $title = 'নতুন খাবারের অর্ডার এসেছে';
        $message = "{$restaurantName}-এ {$order->order_no} অর্ডার এসেছে। মোট বিল ৳{$order->grand_total}।";
        $this->notifyUser((object) ['user_id' => $ownerId, 'id' => $order->id], $title, $message, [
            'type' => 'food_order',
            'role' => 'restaurant_owner',
            'event' => 'new_order',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'restaurant_id' => $order->restaurant_id,
            'screen' => 'food_owner_orders',
        ]);
    }

    private function notifyFoodOrderCustomer(FoodOrder $order): void
    {
        $label = $this->statusLabels()[$order->status] ?? $order->status;
        $restaurantName = $order->restaurant?->name ?: 'রেস্টুরেন্ট';
        $title = match ($order->status) {
            'accepted' => 'আপনার অর্ডার গ্রহণ করা হয়েছে',
            'preparing' => 'খাবার প্রস্তুত হচ্ছে',
            'picked_up' => 'রাইডার খাবার নিয়েছে',
            'on_the_way' => 'আপনার খাবার পথে আছে',
            'delivered' => 'অর্ডার ডেলিভারি সম্পন্ন',
            'rejected' => 'অর্ডারটি গ্রহণ করা হয়নি',
            'cancelled' => 'অর্ডার বাতিল হয়েছে',
            default => 'অর্ডার আপডেট',
        };
        $message = "{$restaurantName}: {$label}। অর্ডার {$order->order_no}";
        $this->notifyUser($order, $title, $message, [
            'type' => 'food_order',
            'role' => 'customer',
            'event' => 'status_update',
            'status' => $order->status,
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'restaurant_id' => $order->restaurant_id,
            'screen' => 'food_order_details',
        ]);
    }

    private function dispatchOrderToNearbyRiders(FoodOrder $order): void
    {
        if ($order->order_type !== 'delivery' || $order->rider_id) {
            Log::info('Rider dispatch skipped: order not dispatchable', [
                'order_id' => $order->id,
                'order_type' => $order->order_type,
                'rider_id' => $order->rider_id,
            ]);
            return;
        }

        $restaurant = $order->restaurant;
        $originLat = $restaurant?->lat !== null ? (float) $restaurant->lat : null;
        $originLng = $restaurant?->lng !== null ? (float) $restaurant->lng : null;
        if ($originLat === null || $originLng === null) {
            Log::info('Rider dispatch skipped: restaurant location missing', ['order_id' => $order->id]);
            return;
        }

        $radiusKm = 20.0;
        $riders = Rider::query()
            ->where('kyc_status', 'approved')
            ->where('account_status', 'active')
            ->where('agreement_accepted', true)
            ->where('availability_status', 'online')
            ->whereNotNull('last_lat')
            ->whereNotNull('last_lng')
            ->where(function ($query): void {
                $query->whereNull('last_location_at')->orWhere('last_location_at', '>=', now()->subMinutes(30));
            })
            ->get()
            ->map(function (Rider $rider) use ($originLat, $originLng): Rider {
                $rider->dispatch_distance_km = $this->distanceKm($originLat, $originLng, (float) $rider->last_lat, (float) $rider->last_lng);
                return $rider;
            })
            ->filter(fn (Rider $rider) => $rider->dispatch_distance_km <= $radiusKm)
            ->sortBy('dispatch_distance_km')
            ->values();

        if ($riders->isEmpty()) {
            Log::info('Rider dispatch skipped: no nearby online riders', ['order_id' => $order->id]);
            return;
        }

        foreach ($riders as $rider) {
            RiderOrderRequest::query()->updateOrCreate(
                ['food_order_id' => $order->id, 'rider_id' => $rider->id],
                [
                    'distance_km' => round((float) $rider->dispatch_distance_km, 2),
                    'restaurant_lat' => $originLat,
                    'restaurant_lng' => $originLng,
                    'status' => 'pending',
                    'notified_at' => now(),
                    'expires_at' => now()->addMinutes(15),
                    'reject_reason' => null,
                ]
            );
        }

        Log::info('Rider dispatch requests created', [
            'order_id' => $order->id,
            'rider_count' => $riders->count(),
            'radius_km' => $radiusKm,
        ]);

        $this->notifyRidersForOrder($order, $riders->pluck('id')->all());
    }

    private function notifyRidersForOrder(FoodOrder $order, array $riderIds): void
    {
        $userIds = Rider::query()->whereIn('id', $riderIds)->pluck('user_id')->all();
        if (! $userIds) {
            return;
        }

        $restaurantName = $order->restaurant?->name ?: 'রেস্টুরেন্ট';
        $title = 'নতুন ডেলিভারি রিকোয়েস্ট';
        $message = "{$restaurantName} থেকে {$order->order_no} অর্ডার ডেলিভারি করতে হবে।";

        foreach ($userIds as $userId) {
            AppNotification::query()->create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'data' => [
                    'type' => 'rider_order_request',
                    'role' => 'rider',
                    'event' => 'new_delivery_request',
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'restaurant_id' => $order->restaurant_id,
                    'screen' => 'rider_dashboard',
                ],
            ]);
        }

        $tokens = DeviceToken::query()->whereIn('user_id', $userIds)->pluck('token')->all();
        if (! $tokens) {
            return;
        }

        try {
            app(FcmService::class)->sendToTokens($tokens, [
                'data' => [
                    'type' => 'rider_order_request',
                    'role' => 'rider',
                    'event' => 'new_delivery_request',
                    'title' => $title,
                    'message' => $message,
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'restaurant_id' => $order->restaurant_id,
                    'screen' => 'rider_dashboard',
                ],
                'notification' => ['title' => $title, 'body' => $message],
            ]);
        } catch (\Throwable $e) {
            Log::error('Rider dispatch notification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    private function notifyUser($order, string $title, string $message, array $data = []): void
    {
        $userId = (int) $order->user_id;
        $data = array_filter($data + [
            'type' => 'food_order',
            'title' => $title,
            'message' => $message,
        ], fn ($value) => $value !== null && $value !== '');

        AppNotification::query()->create(['user_id' => $userId, 'title' => $title, 'message' => $message, 'data' => $data]);
        $tokens = DeviceToken::query()->where('user_id', $userId)->pluck('token')->all();
        if ($tokens) {
            try {
                $results = app(FcmService::class)->sendToTokens($tokens, [
                    'data' => $data,
                    'notification' => ['title' => $title, 'body' => $message],
                ]);
                Log::info('Food order notification sent', [
                    'user_id' => $userId,
                    'tokens' => count($tokens),
                    'data' => $data,
                    'results' => $results,
                ]);
            } catch (\Throwable $e) {
                Log::error('Food order notification failed', [
                    'user_id' => $userId,
                    'tokens' => count($tokens),
                    'data' => $data,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('Food order notification skipped: no device token', [
                'user_id' => $userId,
                'data' => $data,
            ]);
        }
    }
}

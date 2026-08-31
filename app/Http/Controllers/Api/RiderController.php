<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\FoodOrder;
use App\Models\MedicineOrder;
use App\Models\Rider;
use App\Models\RiderDocument;
use App\Models\RiderLocation;
use App\Models\RiderOrderRequest;
use App\Models\RiderRating;
use App\Models\RiderSupportTicket;
use App\Models\RiderWalletEntry;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RiderController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request, false);
        return response()->json([
            'rider' => $rider?->load(['documents', 'walletEntries' => fn ($q) => $q->latest()->limit(20)]),
            'labels' => $this->labels(),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'profile_photo' => ['nullable', 'file', 'image', 'max:4096'],
            'vehicle_type' => ['required', 'in:cycle,bike,car'],
            'vehicle_number' => ['nullable', 'string', 'max:80'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['required', 'string', 'max:40'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('riders/profile', 'public');
        }

        $rider = Rider::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'রাইডার প্রোফাইল সংরক্ষণ হয়েছে।',
            'rider' => $rider->fresh()->load('documents'),
        ]);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        $data = $request->validate([
            'type' => ['required', 'in:nid_front,nid_back,selfie,driving_license,vehicle_paper,bank_mfs'],
            'title' => ['nullable', 'string', 'max:120'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:6144'],
        ]);

        $path = $request->file('file')->store('riders/kyc', 'public');
        $document = RiderDocument::query()->updateOrCreate(
            ['rider_id' => $rider->id, 'type' => $data['type']],
            [
                'title' => $data['title'] ?? $this->documentTitle($data['type']),
                'file_path' => $path,
                'status' => 'pending',
                'note' => null,
            ]
        );

        $rider->update([
            'kyc_status' => 'pending',
            'kyc_submitted_at' => now(),
            'account_status' => $rider->account_status === 'active' ? 'active' : 'pending',
        ]);

        return response()->json([
            'message' => 'ডকুমেন্ট আপলোড হয়েছে। অ্যাডমিন যাচাই করবে।',
            'document' => $document->fresh(),
            'rider' => $rider->fresh()->load('documents'),
        ]);
    }

    public function acceptAgreement(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        $data = $request->validate([
            'accepted' => ['required', 'boolean'],
        ]);
        abort_unless($data['accepted'], 422, 'চুক্তি গ্রহণ করা আবশ্যক।');

        $rider->update([
            'agreement_accepted' => true,
            'agreement_accepted_at' => now(),
            'agreement_status' => $rider->account_status === 'active' ? 'active' : 'pending',
        ]);

        return response()->json([
            'message' => 'রাইডার চুক্তি গ্রহণ করা হয়েছে।',
            'rider' => $rider->fresh(),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        $todayFoodOrders = FoodOrder::query()->where('rider_id', $rider->id)->whereDate('created_at', today());
        $todayMedicineOrders = MedicineOrder::query()->where('rider_id', $rider->id)->whereDate('created_at', today());
        $activeFoodOrders = FoodOrder::query()
            ->with('restaurant:id,name,phone,address,lat,lng', 'items')
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'preparing', 'picked_up', 'on_the_way'])
            ->latest()
            ->get();
        $activeMedicineOrders = MedicineOrder::query()
            ->with('items')
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'preparing', 'picked_up', 'on_the_way'])
            ->latest()
            ->get();
        $activeOrders = $activeFoodOrders
            ->map(fn (FoodOrder $order) => $this->decorateRiderOrder($order, 'food'))
            ->merge($activeMedicineOrders->map(fn (MedicineOrder $order) => $this->decorateRiderOrder($order, 'medicine')))
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'rider' => $rider,
            'stats' => [
                'today_deliveries' => (clone $todayFoodOrders)->where('status', 'delivered')->count()
                    + (clone $todayMedicineOrders)->where('status', 'delivered')->count(),
                'today_earning' => (float) (clone $todayFoodOrders)->where('status', 'delivered')->sum('rider_earning')
                    + (float) (clone $todayMedicineOrders)->where('status', 'delivered')->sum('rider_earning'),
                'pending_payout' => (float) $rider->pending_payout,
                'wallet_balance' => (float) $rider->wallet_balance,
                'cash_in_hand' => (float) $rider->cash_in_hand,
                'rating' => (float) $rider->rating,
            ],
            'active_orders' => $activeOrders,
            'new_requests' => $this->newRequests($rider),
            'labels' => $this->labels(),
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        abort_unless($rider->kyc_status === 'approved' && $rider->account_status === 'active', 422, 'অ্যাকাউন্ট অনুমোদিত হলে অনলাইন হওয়া যাবে।');
        $data = $request->validate([
            'availability_status' => ['required', 'in:offline,online,busy'],
        ]);
        $rider->update($data);
        return response()->json(['message' => 'স্ট্যাটাস আপডেট হয়েছে।', 'rider' => $rider->fresh()]);
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'food_order_id' => ['nullable', 'exists:food_orders,id'],
        ]);
        RiderLocation::query()->create($data + ['rider_id' => $rider->id]);
        $rider->update([
            'last_lat' => $data['lat'],
            'last_lng' => $data['lng'],
            'last_location_at' => now(),
        ]);
        $this->dispatchNearbyPendingOrders($rider->fresh());
        return response()->json(['message' => 'লোকেশন আপডেট হয়েছে।']);
    }

    public function orders(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        $foodOrders = FoodOrder::query()
            ->with('restaurant:id,name,phone,address,lat,lng', 'items')
            ->where('rider_id', $rider->id)
            ->latest()
            ->limit((int) min(max((int) $request->query('per_page', 30), 1), 100))
            ->get()
            ->map(fn (FoodOrder $order) => $this->decorateRiderOrder($order, 'food'));
        $medicineOrders = MedicineOrder::query()
            ->with('items')
            ->where('rider_id', $rider->id)
            ->latest()
            ->limit((int) min(max((int) $request->query('per_page', 30), 1), 100))
            ->get()
            ->map(fn (MedicineOrder $order) => $this->decorateRiderOrder($order, 'medicine'));

        return response()->json([
            'data' => $foodOrders->merge($medicineOrders)->sortByDesc('created_at')->values(),
        ]);
    }

    public function acceptOrder(Request $request, int $id): JsonResponse
    {
        $rider = $this->riderFor($request);
        abort_unless($rider->kyc_status === 'approved' && $rider->account_status === 'active', 422, 'অনুমোদিত রাইডার ছাড়া অর্ডার গ্রহণ করা যাবে না।');

        $order = DB::transaction(function () use ($request, $id, $rider) {
            [$order, $requestRow, $serviceType] = $this->pendingOrderRequestFor($request, $id, $rider, lock: true);

            abort_unless($requestRow->status === 'pending', 422, 'এই ডেলিভারি রিকোয়েস্ট আর চালু নেই।');
            abort_unless($requestRow->expires_at === null || $requestRow->expires_at->isFuture(), 422, 'এই ডেলিভারি রিকোয়েস্টের সময় শেষ।');
            abort_unless($order->rider_id === null, 409, 'অন্য রাইডার ইতিমধ্যে অর্ডারটি নিয়েছে।');
            abort_unless(in_array($order->status, ['pending', 'accepted', 'preparing'], true), 422, 'এই অর্ডার গ্রহণ করা যাবে না।');

            $earning = $this->calculateEarning($rider, $order);
            $payload = [
                'rider_id' => $rider->id,
                'delivery_person_name' => $rider->name,
                'delivery_person_phone' => $rider->phone,
                'rider_assigned_at' => now(),
                'rider_earning' => $earning,
            ];
            if ($serviceType === 'medicine' && $order->status === 'pending') {
                $payload['status'] = 'accepted';
                $payload['accepted_at'] = now();
            }
            $order->update($payload);
            $requestRow->update(['status' => 'accepted', 'responded_at' => now()]);
            RiderOrderRequest::query()
                ->where('rider_id', $rider->id)
                ->where($serviceType === 'medicine' ? 'medicine_order_id' : 'food_order_id', $order->id)
                ->where('id', '!=', $requestRow->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired', 'responded_at' => now()]);
            $rider->update(['availability_status' => 'busy']);

            return $this->decorateRiderOrder($this->freshOrderForService($order, $serviceType), $serviceType);
        });

        return response()->json(['message' => 'অর্ডার গ্রহণ করা হয়েছে।', 'order' => $order]);
    }

    public function rejectOrder(Request $request, int $id): JsonResponse
    {
        $rider = $this->riderFor($request);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:160']]);
        [$order, $requestRow, $serviceType] = $this->pendingOrderRequestFor($request, $id, $rider);
        $requestRow->update([
                'status' => 'rejected',
                'responded_at' => now(),
                'reject_reason' => $data['reason'] ?? null,
            ]);
        return response()->json(['message' => 'অর্ডার রিজেক্ট করা হয়েছে।']);
    }

    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $rider = $this->riderFor($request);
        $data = $request->validate([
            'status' => ['required', 'in:picked_up,on_the_way,delivered'],
            'delivery_otp' => ['nullable', 'string', 'max:12'],
            'proof_photo' => ['nullable', 'file', 'image', 'max:4096'],
            'cash_collected' => ['nullable', 'numeric', 'min:0'],
        ]);
        [$order, $serviceType] = $this->assignedOrderFor($request, $id, $rider);
        abort_unless(in_array($order->status, ['accepted', 'preparing', 'picked_up', 'on_the_way'], true), 422, 'স্ট্যাটাস আপডেট করা যাবে না।');

        if ($data['status'] === 'delivered' && $order->delivery_otp && ($data['delivery_otp'] ?? '') !== $order->delivery_otp) {
            abort(422, 'ডেলিভারি OTP সঠিক নয়।');
        }

        $payload = ['status' => $data['status']];
        if ($data['status'] === 'picked_up') {
            $payload['picked_up_at'] = now();
        }
        if ($data['status'] === 'delivered') {
            $payload['delivered_at'] = now();
            $payload['cash_collected'] = (float) ($data['cash_collected'] ?? $order->grand_total);
            if ($request->hasFile('proof_photo')) {
                $payload['delivery_proof_photo'] = $request->file('proof_photo')->store('riders/delivery-proof', 'public');
            }
        }
        $order->update($payload);

        if ($data['status'] === 'delivered') {
            $this->recordDeliveryEarning($rider->fresh(), $order->fresh());
        }

        return response()->json([
            'message' => 'অর্ডার স্ট্যাটাস আপডেট হয়েছে।',
            'order' => $this->decorateRiderOrder($this->freshOrderForService($order, $serviceType), $serviceType),
        ]);
    }

    public function wallet(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        return response()->json([
            'summary' => [
                'wallet_balance' => (float) $rider->wallet_balance,
                'pending_payout' => (float) $rider->pending_payout,
                'cash_in_hand' => (float) $rider->cash_in_hand,
            ],
            'entries' => RiderWalletEntry::query()->where('rider_id', $rider->id)->latest()->paginate(50),
        ]);
    }

    public function supportTickets(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        return response()->json(RiderSupportTicket::query()->where('rider_id', $rider->id)->latest()->paginate(30));
    }

    public function createSupportTicket(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string'],
            'food_order_id' => ['nullable', 'exists:food_orders,id'],
        ]);
        $ticket = RiderSupportTicket::query()->create($data + ['rider_id' => $rider->id]);
        return response()->json(['message' => 'সাপোর্ট টিকিট পাঠানো হয়েছে।', 'ticket' => $ticket], 201);
    }

    public function rate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
            'food_order_id' => ['nullable', 'exists:food_orders,id'],
        ]);
        $rider = Rider::query()->findOrFail($id);
        $rating = RiderRating::query()->updateOrCreate(
            ['rider_id' => $rider->id, 'food_order_id' => $data['food_order_id'] ?? null],
            $data + ['rider_id' => $rider->id, 'user_id' => $request->user()->id]
        );
        $rider->update([
            'rating' => RiderRating::query()->where('rider_id', $rider->id)->avg('rating') ?: 0,
            'rating_count' => RiderRating::query()->where('rider_id', $rider->id)->count(),
        ]);
        return response()->json(['message' => 'রাইডার রিভিউ জমা হয়েছে।', 'rating' => $rating]);
    }

    private function riderFor(Request $request, bool $required = true): ?Rider
    {
        $rider = Rider::query()->where('user_id', $request->user()->id)->first();
        if ($required) {
            abort_unless($rider, 404, 'রাইডার প্রোফাইল পাওয়া যায়নি।');
        }
        return $rider;
    }

    private function newRequests(Rider $rider)
    {
        if ($rider->account_status !== 'active' || $rider->kyc_status !== 'approved') {
            return [];
        }
        $foodOrders = FoodOrder::query()
            ->with('restaurant:id,name,phone,address,lat,lng', 'items')
            ->whereIn('id', RiderOrderRequest::query()
                ->select('food_order_id')
                ->where('rider_id', $rider->id)
                ->where('status', 'pending')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                }))
            ->whereNull('rider_id')
            ->whereIn('status', ['accepted', 'preparing'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (FoodOrder $order) => $this->decorateRiderOrder($order, 'food'));
        $medicineOrders = MedicineOrder::query()
            ->with('items')
            ->whereIn('id', RiderOrderRequest::query()
                ->select('medicine_order_id')
                ->where('rider_id', $rider->id)
                ->where('status', 'pending')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                }))
            ->whereNull('rider_id')
            ->whereIn('status', ['pending', 'accepted', 'preparing'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (MedicineOrder $order) => $this->decorateRiderOrder($order, 'medicine'));

        return $foodOrders->merge($medicineOrders)->sortByDesc('created_at')->values();
    }

    private function recordDeliveryEarning(Rider $rider, FoodOrder|MedicineOrder $order): void
    {
        DB::transaction(function () use ($rider, $order): void {
            $earning = (float) ($order->rider_earning ?: $this->calculateEarning($rider, $order));
            $cash = (float) $order->cash_collected;
            $rider->wallet_balance = (float) $rider->wallet_balance + $earning;
            $rider->pending_payout = (float) $rider->pending_payout + $earning;
            $rider->cash_in_hand = (float) $rider->cash_in_hand + $cash;
            $rider->availability_status = 'online';
            $rider->save();
            $order->update(['rider_earning' => $earning]);
            RiderWalletEntry::query()->create([
                'rider_id' => $rider->id,
                'food_order_id' => $order instanceof FoodOrder ? $order->id : null,
                'medicine_order_id' => $order instanceof MedicineOrder ? $order->id : null,
                'type' => 'earning',
                'amount' => $earning,
                'balance_after' => $rider->wallet_balance,
                'title' => 'ডেলিভারি আয়',
                'note' => $order->order_no,
            ]);
            if ($cash > 0) {
                RiderWalletEntry::query()->create([
                    'rider_id' => $rider->id,
                    'food_order_id' => $order instanceof FoodOrder ? $order->id : null,
                    'medicine_order_id' => $order instanceof MedicineOrder ? $order->id : null,
                    'type' => 'cash_collection',
                    'amount' => $cash,
                    'balance_after' => $rider->wallet_balance,
                    'title' => 'ক্যাশ সংগ্রহ',
                    'note' => $order->order_no,
                ]);
            }
        });
    }

    private function calculateEarning(Rider $rider, FoodOrder|MedicineOrder $order): float
    {
        return match ($rider->commission_type) {
            'percentage' => round(((float) $order->delivery_fee) * ((float) $rider->commission_value / 100), 2),
            'zone_based' => round(max((float) $rider->commission_value, (float) $order->delivery_fee * 0.65), 2),
            default => round((float) ($rider->commission_value ?: $order->delivery_fee), 2),
        };
    }

    private function pendingOrderRequestFor(Request $request, int $id, Rider $rider, bool $lock = false): array
    {
        $serviceType = $request->input('service_type');
        $requestQuery = RiderOrderRequest::query()
            ->where('rider_id', $rider->id)
            ->where('status', 'pending');
        if ($lock) {
            $requestQuery->lockForUpdate();
        }
        if ($serviceType === 'medicine') {
            $requestQuery->where('medicine_order_id', $id);
        } elseif ($serviceType === 'food') {
            $requestQuery->where('food_order_id', $id);
        } else {
            $requestQuery->where(function ($query) use ($id): void {
                $query->where('food_order_id', $id)->orWhere('medicine_order_id', $id);
            });
        }
        $requestRow = $requestQuery->firstOrFail();
        $serviceType = $requestRow->medicine_order_id ? 'medicine' : 'food';
        $orderQuery = $serviceType === 'medicine'
            ? MedicineOrder::query()->with('items')->where('id', $requestRow->medicine_order_id)
            : FoodOrder::query()->with('restaurant:id,name,phone,address,lat,lng', 'items')->where('id', $requestRow->food_order_id);
        if ($lock) {
            $orderQuery->lockForUpdate();
        }

        return [$orderQuery->firstOrFail(), $requestRow, $serviceType];
    }

    private function assignedOrderFor(Request $request, int $id, Rider $rider): array
    {
        $serviceType = $request->input('service_type');
        if ($serviceType === 'medicine') {
            return [MedicineOrder::query()->with('items')->where('rider_id', $rider->id)->findOrFail($id), 'medicine'];
        }
        if ($serviceType === 'food') {
            return [FoodOrder::query()->with('restaurant:id,name,phone,address,lat,lng', 'items')->where('rider_id', $rider->id)->findOrFail($id), 'food'];
        }
        $food = FoodOrder::query()->with('restaurant:id,name,phone,address,lat,lng', 'items')->where('rider_id', $rider->id)->find($id);
        if ($food) {
            return [$food, 'food'];
        }
        return [MedicineOrder::query()->with('items')->where('rider_id', $rider->id)->findOrFail($id), 'medicine'];
    }

    private function freshOrderForService(FoodOrder|MedicineOrder $order, string $serviceType): FoodOrder|MedicineOrder
    {
        return $serviceType === 'medicine'
            ? $order->fresh('items')
            : $order->fresh(['restaurant:id,name,phone,address,lat,lng', 'items']);
    }

    private function decorateRiderOrder(FoodOrder|MedicineOrder $order, string $serviceType): array
    {
        $data = $order->toArray();
        $data['service_type'] = $serviceType;
        if ($serviceType === 'medicine') {
            $data['restaurant'] = [
                'name' => 'Medicine Store',
                'phone' => config('app.name'),
                'address' => 'Medicine pickup point',
                'lat' => null,
                'lng' => null,
            ];
            $data['items'] = collect($data['items'] ?? [])->map(function (array $item): array {
                $item['name'] = $item['brand_name'] ?? 'Medicine';
                return $item;
            })->values()->all();
        }

        return $data;
    }

    private function dispatchNearbyPendingOrders(Rider $rider): void
    {
        if (
            $rider->kyc_status !== 'approved'
            || $rider->account_status !== 'active'
            || ! $rider->agreement_accepted
            || $rider->availability_status !== 'online'
            || $rider->last_lat === null
            || $rider->last_lng === null
        ) {
            return;
        }

        $radiusKm = 20.0;
        $orders = FoodOrder::query()
            ->with('restaurant:id,name,phone,address,lat,lng')
            ->where('order_type', 'delivery')
            ->whereNull('rider_id')
            ->whereIn('status', ['accepted', 'preparing'])
            ->latest()
            ->limit(50)
            ->get()
            ->filter(function (FoodOrder $order) use ($rider, $radiusKm): bool {
                if ($order->restaurant?->lat === null || $order->restaurant?->lng === null) {
                    return false;
                }

                $distance = $this->distanceKm(
                    (float) $order->restaurant->lat,
                    (float) $order->restaurant->lng,
                    (float) $rider->last_lat,
                    (float) $rider->last_lng
                );
                $order->rider_dispatch_distance_km = $distance;

                return $distance <= $radiusKm;
            });

        foreach ($orders as $order) {
            $request = RiderOrderRequest::query()->updateOrCreate(
                ['food_order_id' => $order->id, 'rider_id' => $rider->id],
                [
                    'distance_km' => round((float) $order->rider_dispatch_distance_km, 2),
                    'restaurant_lat' => (float) $order->restaurant->lat,
                    'restaurant_lng' => (float) $order->restaurant->lng,
                    'status' => 'pending',
                    'notified_at' => now(),
                    'expires_at' => now()->addMinutes(15),
                    'reject_reason' => null,
                ]
            );

            if ($request->wasRecentlyCreated || $request->wasChanged(['status', 'expires_at'])) {
                $this->notifyRiderAboutOrder($rider, $order);
            }
        }

        $medicineOrders = MedicineOrder::query()
            ->whereNull('rider_id')
            ->whereIn('status', ['pending', 'accepted', 'preparing'])
            ->latest()
            ->limit(50)
            ->get();

        foreach ($medicineOrders as $order) {
            $request = RiderOrderRequest::query()->updateOrCreate(
                ['medicine_order_id' => $order->id, 'rider_id' => $rider->id],
                [
                    'food_order_id' => null,
                    'distance_km' => null,
                    'restaurant_lat' => null,
                    'restaurant_lng' => null,
                    'status' => 'pending',
                    'notified_at' => now(),
                    'expires_at' => now()->addMinutes(15),
                    'reject_reason' => null,
                ]
            );

            if ($request->wasRecentlyCreated || $request->wasChanged(['status', 'expires_at'])) {
                $this->notifyRiderAboutMedicineOrder($rider, $order);
            }
        }
    }

    private function notifyRiderAboutOrder(Rider $rider, FoodOrder $order): void
    {
        $restaurantName = $order->restaurant?->name ?: 'রেস্টুরেন্ট';
        $title = 'নতুন ডেলিভারি রিকোয়েস্ট';
        $message = "{$restaurantName} থেকে {$order->order_no} অর্ডার ডেলিভারি করতে হবে।";
        $data = [
            'type' => 'rider_order_request',
            'role' => 'rider',
            'event' => 'new_delivery_request',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'restaurant_id' => $order->restaurant_id,
            'screen' => 'rider_dashboard',
        ];

        AppNotification::query()->create([
            'user_id' => $rider->user_id,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        $tokens = DeviceToken::query()->where('user_id', $rider->user_id)->pluck('token')->all();
        if (! $tokens) {
            return;
        }

        try {
            app(FcmService::class)->sendToTokens($tokens, [
                'data' => $data + ['title' => $title, 'message' => $message],
                'notification' => ['title' => $title, 'body' => $message],
            ]);
        } catch (\Throwable $e) {
            Log::error('Rider location dispatch notification failed', [
                'order_id' => $order->id,
                'rider_id' => $rider->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyRiderAboutMedicineOrder(Rider $rider, MedicineOrder $order): void
    {
        $title = 'নতুন মেডিসিন ডেলিভারি';
        $message = "{$order->order_no} মেডিসিন অর্ডার ডেলিভারি করতে হবে।";
        $data = [
            'type' => 'rider_order_request',
            'role' => 'rider',
            'service_type' => 'medicine',
            'event' => 'new_delivery_request',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'screen' => 'rider_dashboard',
        ];

        AppNotification::query()->create([
            'user_id' => $rider->user_id,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        $tokens = DeviceToken::query()->where('user_id', $rider->user_id)->pluck('token')->all();
        if (! $tokens) {
            return;
        }

        try {
            app(FcmService::class)->sendToTokens($tokens, [
                'data' => $data + ['title' => $title, 'message' => $message],
                'notification' => ['title' => $title, 'body' => $message],
            ]);
        } catch (\Throwable $e) {
            Log::error('Medicine rider location dispatch notification failed', [
                'order_id' => $order->id,
                'rider_id' => $rider->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthKm = 6371;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return $earthKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function documentTitle(string $type): string
    {
        return [
            'nid_front' => 'এনআইডি সামনের ছবি',
            'nid_back' => 'এনআইডি পিছনের ছবি',
            'selfie' => 'সেলফি যাচাই',
            'driving_license' => 'ড্রাইভিং লাইসেন্স',
            'vehicle_paper' => 'যানবাহনের কাগজ',
            'bank_mfs' => 'ব্যাংক/মোবাইল ব্যাংকিং তথ্য',
        ][$type] ?? $type;
    }

    private function labels(): array
    {
        return [
            'vehicle_types' => ['cycle' => 'সাইকেল', 'bike' => 'মোটরসাইকেল', 'car' => 'গাড়ি'],
            'kyc_statuses' => ['draft' => 'খসড়া', 'pending' => 'পর্যালোচনায় আছে', 'approved' => 'অনুমোদিত', 'rejected' => 'বাতিল'],
            'order_statuses' => ['assigned' => 'অ্যাসাইন করা হয়েছে', 'accepted' => 'গ্রহণ করা হয়েছে', 'picked_up' => 'খাবার নেওয়া হয়েছে', 'on_the_way' => 'পথে আছে', 'delivered' => 'ডেলিভারি সম্পন্ন'],
            'payment_cycles' => ['daily' => 'দৈনিক', 'weekly' => 'সাপ্তাহিক', 'monthly' => 'মাসিক'],
            'commission_types' => ['fixed' => 'প্রতি ডেলিভারিতে নির্দিষ্ট', 'percentage' => 'শতকরা কমিশন', 'zone_based' => 'এলাকা অনুযায়ী'],
        ];
    }
}

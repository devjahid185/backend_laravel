<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodOrder;
use App\Models\Rider;
use App\Models\RiderDocument;
use App\Models\RiderLocation;
use App\Models\RiderOrderRequest;
use App\Models\RiderRating;
use App\Models\RiderSupportTicket;
use App\Models\RiderWalletEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $todayOrders = FoodOrder::query()->where('rider_id', $rider->id)->whereDate('created_at', today());
        $activeOrders = FoodOrder::query()
            ->with('restaurant:id,name,phone,address,lat,lng', 'items')
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'preparing', 'picked_up', 'on_the_way'])
            ->latest()
            ->get();

        return response()->json([
            'rider' => $rider,
            'stats' => [
                'today_deliveries' => (clone $todayOrders)->where('status', 'delivered')->count(),
                'today_earning' => (float) (clone $todayOrders)->where('status', 'delivered')->sum('rider_earning'),
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
        return response()->json(['message' => 'লোকেশন আপডেট হয়েছে।']);
    }

    public function orders(Request $request): JsonResponse
    {
        $rider = $this->riderFor($request);
        $orders = FoodOrder::query()
            ->with('restaurant:id,name,phone,address,lat,lng', 'items')
            ->where('rider_id', $rider->id)
            ->latest()
            ->paginate((int) min(max((int) $request->query('per_page', 30), 1), 100));
        return response()->json($orders);
    }

    public function acceptOrder(Request $request, int $id): JsonResponse
    {
        $rider = $this->riderFor($request);
        abort_unless($rider->kyc_status === 'approved' && $rider->account_status === 'active', 422, 'অনুমোদিত রাইডার ছাড়া অর্ডার গ্রহণ করা যাবে না।');

        $order = DB::transaction(function () use ($id, $rider): FoodOrder {
            $order = FoodOrder::query()->with('restaurant')->where('id', $id)->lockForUpdate()->firstOrFail();
            $requestRow = RiderOrderRequest::query()
                ->where('food_order_id', $order->id)
                ->where('rider_id', $rider->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($requestRow->status === 'pending', 422, 'এই ডেলিভারি রিকোয়েস্ট আর চালু নেই।');
            abort_unless($requestRow->expires_at === null || $requestRow->expires_at->isFuture(), 422, 'এই ডেলিভারি রিকোয়েস্টের সময় শেষ।');
            abort_unless($order->rider_id === null, 409, 'অন্য রাইডার ইতিমধ্যে অর্ডারটি নিয়েছে।');
            abort_unless(in_array($order->status, ['accepted', 'preparing'], true), 422, 'এই অর্ডার গ্রহণ করা যাবে না।');

            $earning = $this->calculateEarning($rider, $order);
            $order->update([
                'rider_id' => $rider->id,
                'delivery_person_name' => $rider->name,
                'delivery_person_phone' => $rider->phone,
                'rider_assigned_at' => now(),
                'rider_earning' => $earning,
            ]);
            $requestRow->update(['status' => 'accepted', 'responded_at' => now()]);
            RiderOrderRequest::query()
                ->where('food_order_id', $order->id)
                ->where('id', '!=', $requestRow->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired', 'responded_at' => now()]);
            $rider->update(['availability_status' => 'busy']);

            return $order->fresh(['restaurant:id,name,phone,address,lat,lng', 'items']);
        });

        return response()->json(['message' => 'অর্ডার গ্রহণ করা হয়েছে।', 'order' => $order]);
    }

    public function rejectOrder(Request $request, int $id): JsonResponse
    {
        $rider = $this->riderFor($request);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:160']]);
        RiderOrderRequest::query()
            ->where('food_order_id', $id)
            ->where('rider_id', $rider->id)
            ->where('status', 'pending')
            ->update([
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
        $order = FoodOrder::query()->where('rider_id', $rider->id)->findOrFail($id);
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

        return response()->json(['message' => 'অর্ডার স্ট্যাটাস আপডেট হয়েছে।', 'order' => $order->fresh()]);
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
        return FoodOrder::query()
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
            ->get();
    }

    private function recordDeliveryEarning(Rider $rider, FoodOrder $order): void
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
                'food_order_id' => $order->id,
                'type' => 'earning',
                'amount' => $earning,
                'balance_after' => $rider->wallet_balance,
                'title' => 'ডেলিভারি আয়',
                'note' => $order->order_no,
            ]);
            if ($cash > 0) {
                RiderWalletEntry::query()->create([
                    'rider_id' => $rider->id,
                    'food_order_id' => $order->id,
                    'type' => 'cash_collection',
                    'amount' => $cash,
                    'balance_after' => $rider->wallet_balance,
                    'title' => 'ক্যাশ সংগ্রহ',
                    'note' => $order->order_no,
                ]);
            }
        });
    }

    private function calculateEarning(Rider $rider, FoodOrder $order): float
    {
        return match ($rider->commission_type) {
            'percentage' => round(((float) $order->delivery_fee) * ((float) $rider->commission_value / 100), 2),
            'zone_based' => round(max((float) $rider->commission_value, (float) $order->delivery_fee * 0.65), 2),
            default => round((float) ($rider->commission_value ?: $order->delivery_fee), 2),
        };
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

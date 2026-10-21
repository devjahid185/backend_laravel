<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodOrder;
use App\Models\MedicineOrder;
use App\Models\Rider;
use App\Models\RiderLocation;
use App\Models\RiderOrderRequest;
use App\Models\RiderRating;
use App\Models\RiderSupportTicket;
use App\Models\RiderWalletEntry;
use Illuminate\Http\JsonResponse;

class AdminRiderController extends Controller
{
    public function show(int $rider): JsonResponse
    {
        $riderModel = Rider::query()
            ->with(['documents', 'user:id,name,phone,email'])
            ->findOrFail($rider);

        $foodOrders = FoodOrder::query()
            ->with('restaurant:id,name,phone,address')
            ->where('rider_id', $riderModel->id)
            ->latest()
            ->limit(30)
            ->get();

        $medicineOrders = MedicineOrder::query()
            ->where('rider_id', $riderModel->id)
            ->latest()
            ->limit(30)
            ->get();

        $orders = $foodOrders
            ->map(fn (FoodOrder $order) => $this->formatFoodOrder($order))
            ->merge($medicineOrders->map(fn (MedicineOrder $order) => $this->formatMedicineOrder($order)))
            ->sortByDesc('created_at')
            ->values()
            ->take(40);

        $walletEntries = RiderWalletEntry::query()
            ->where('rider_id', $riderModel->id)
            ->latest()
            ->limit(40)
            ->get();

        $orderRequests = RiderOrderRequest::query()
            ->with([
                'foodOrder:id,order_no,status,grand_total,receiver_name,receiver_phone',
                'medicineOrder:id,order_no,status,grand_total,receiver_name,receiver_phone',
            ])
            ->where('rider_id', $riderModel->id)
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (RiderOrderRequest $request) => [
                'id' => $request->id,
                'service_type' => $request->medicine_order_id ? 'medicine' : 'food',
                'order_id' => $request->medicine_order_id ?: $request->food_order_id,
                'order_no' => $request->medicineOrder?->order_no ?: $request->foodOrder?->order_no,
                'order_status' => $request->medicineOrder?->status ?: $request->foodOrder?->status,
                'customer' => $request->medicineOrder?->receiver_name ?: $request->foodOrder?->receiver_name,
                'customer_phone' => $request->medicineOrder?->receiver_phone ?: $request->foodOrder?->receiver_phone,
                'grand_total' => $request->medicineOrder?->grand_total ?: $request->foodOrder?->grand_total,
                'distance_km' => $request->distance_km,
                'status' => $request->status,
                'reject_reason' => $request->reject_reason,
                'notified_at' => $request->notified_at,
                'responded_at' => $request->responded_at,
                'expires_at' => $request->expires_at,
                'created_at' => $request->created_at,
            ]);

        $ratings = RiderRating::query()
            ->where('rider_id', $riderModel->id)
            ->latest()
            ->limit(30)
            ->get();

        $supportTickets = RiderSupportTicket::query()
            ->where('rider_id', $riderModel->id)
            ->latest()
            ->limit(30)
            ->get();

        $locations = RiderLocation::query()
            ->where('rider_id', $riderModel->id)
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'rider' => $riderModel,
            'stats' => $this->stats($riderModel),
            'orders' => $orders,
            'wallet_entries' => $walletEntries,
            'order_requests' => $orderRequests,
            'ratings' => $ratings,
            'support_tickets' => $supportTickets,
            'locations' => $locations,
        ]);
    }

    private function stats(Rider $rider): array
    {
        $foodOrders = FoodOrder::query()->where('rider_id', $rider->id);
        $medicineOrders = MedicineOrder::query()->where('rider_id', $rider->id);

        return [
            'total_orders' => (clone $foodOrders)->count() + (clone $medicineOrders)->count(),
            'delivered_orders' => (clone $foodOrders)->where('status', 'delivered')->count() + (clone $medicineOrders)->where('status', 'delivered')->count(),
            'active_orders' => (clone $foodOrders)->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])->count() + (clone $medicineOrders)->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])->count(),
            'cancelled_orders' => (clone $foodOrders)->whereIn('status', ['cancelled', 'rejected'])->count() + (clone $medicineOrders)->whereIn('status', ['cancelled', 'rejected'])->count(),
            'food_orders' => (clone $foodOrders)->count(),
            'medicine_orders' => (clone $medicineOrders)->count(),
            'total_earning' => round((float) (clone $foodOrders)->sum('rider_earning') + (float) (clone $medicineOrders)->sum('rider_earning'), 2),
            'wallet_balance' => $rider->wallet_balance,
            'pending_payout' => $rider->pending_payout,
            'cash_in_hand' => $rider->cash_in_hand,
            'average_rating' => $rider->rating,
            'rating_count' => $rider->rating_count,
            'pending_requests' => RiderOrderRequest::query()->where('rider_id', $rider->id)->where('status', 'pending')->count(),
            'open_support_tickets' => RiderSupportTicket::query()->where('rider_id', $rider->id)->whereIn('status', ['open', 'reviewing'])->count(),
        ];
    }

    private function formatFoodOrder(FoodOrder $order): array
    {
        return [
            'id' => $order->id,
            'service_type' => 'food',
            'order_no' => $order->order_no,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'receiver_name' => $order->receiver_name,
            'receiver_phone' => $order->receiver_phone,
            'delivery_address' => $order->delivery_address,
            'merchant_name' => $order->restaurant?->name,
            'merchant_phone' => $order->restaurant?->phone,
            'items_total' => $order->items_total,
            'delivery_fee' => $order->delivery_fee,
            'grand_total' => $order->grand_total,
            'rider_earning' => $order->rider_earning,
            'cash_collected' => $order->cash_collected,
            'rider_assigned_at' => $order->rider_assigned_at,
            'picked_up_at' => $order->picked_up_at,
            'delivered_at' => $order->delivered_at,
            'cancelled_at' => $order->cancelled_at,
            'created_at' => $order->created_at,
        ];
    }

    private function formatMedicineOrder(MedicineOrder $order): array
    {
        return [
            'id' => $order->id,
            'service_type' => 'medicine',
            'order_no' => $order->order_no,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'receiver_name' => $order->receiver_name,
            'receiver_phone' => $order->receiver_phone,
            'delivery_address' => $order->delivery_address,
            'merchant_name' => 'Medicine',
            'merchant_phone' => null,
            'items_total' => $order->items_total,
            'delivery_fee' => $order->delivery_fee,
            'grand_total' => $order->grand_total,
            'rider_earning' => $order->rider_earning,
            'cash_collected' => $order->cash_collected,
            'rider_assigned_at' => $order->rider_assigned_at,
            'picked_up_at' => $order->picked_up_at,
            'delivered_at' => $order->delivered_at,
            'cancelled_at' => $order->cancelled_at,
            'created_at' => $order->created_at,
        ];
    }
}

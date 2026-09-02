<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodDonor;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\CarRental;
use App\Models\CourierOffice;
use App\Models\Doctor;
use App\Models\DoctorCategory;
use App\Models\EducationInstitute;
use App\Models\EducationCategory;
use App\Models\ElectricityOffice;
use App\Models\EmergencyContact;
use App\Models\Faq;
use App\Models\FoodAddress;
use App\Models\FoodBanner;
use App\Models\FoodCategory;
use App\Models\FoodCoupon;
use App\Models\FoodDeliverySetting;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\FoodOrderSupportTicket;
use App\Models\FoodReview;
use App\Models\Hospital;
use App\Models\HospitalCategory;
use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Models\HomeServiceShortcut;
use App\Models\JobPost;
use App\Models\LaunchService;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceCategory;
use App\Models\MedicineItem;
use App\Models\MedicineOrder;
use App\Models\MedicineOrderItem;
use App\Models\Message;
use App\Models\News;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Models\Rider;
use App\Models\RiderDocument;
use App\Models\RiderLocation;
use App\Models\RiderRating;
use App\Models\RiderSupportTicket;
use App\Models\RiderWalletEntry;
use App\Models\UpdatePost;
use App\Models\Worker;
use App\Models\WorkerCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminResourceController extends Controller
{
    private const RESOURCE_MAP = [
        'workers' => Worker::class,
        'businesses' => Business::class,
        'marketplace' => MarketplaceItem::class,
        'jobs' => JobPost::class,
        'doctors' => Doctor::class,
        'hospitals' => Hospital::class,
        'hotels' => Hotel::class,
        'restaurants' => Restaurant::class,
        'home-service-shortcuts' => HomeServiceShortcut::class,
        'food-categories' => FoodCategory::class,
        'food-banners' => FoodBanner::class,
        'food-items' => FoodItem::class,
        'food-addresses' => FoodAddress::class,
        'food-coupons' => FoodCoupon::class,
        'food-orders' => FoodOrder::class,
        'food-order-support-tickets' => FoodOrderSupportTicket::class,
        'food-reviews' => FoodReview::class,
        'medicine-items' => MedicineItem::class,
        'medicine-orders' => MedicineOrder::class,
        'medicine-order-items' => MedicineOrderItem::class,
        'riders' => Rider::class,
        'rider-documents' => RiderDocument::class,
        'rider-wallet' => RiderWalletEntry::class,
        'rider-locations' => RiderLocation::class,
        'rider-ratings' => RiderRating::class,
        'rider-support-tickets' => RiderSupportTicket::class,
        'property' => Property::class,
        'education' => EducationInstitute::class,
        'blood' => BloodDonor::class,
        'courier' => CourierOffice::class,
        'car-rental' => CarRental::class,
        'launches' => LaunchService::class,
        'electricity' => ElectricityOffice::class,
        'emergency' => EmergencyContact::class,
        'news' => News::class,
        'notices' => Notice::class,
        'updates' => UpdatePost::class,
        'faqs' => Faq::class,
        'messages' => Message::class,
        'payments' => Payment::class,
        'worker-categories' => WorkerCategory::class,
        'business-categories' => BusinessCategory::class,
        'marketplace-categories' => MarketplaceCategory::class,
        'doctor-categories' => DoctorCategory::class,
        'hospital-categories' => HospitalCategory::class,
        'hotel-categories' => HotelCategory::class,
        'restaurant-categories' => RestaurantCategory::class,
        'property-categories' => PropertyCategory::class,
        'education-categories' => EducationCategory::class,
    ];

    public function index(Request $request, string $resource): JsonResponse
    {
        $model = $this->resolveModel($resource);
        if (! $model) {
            return response()->json(['message' => 'Resource not supported.'], 422);
        }

        $query = $model::query();
        if ($resource === 'food-orders') {
            $query->with([
                'restaurant:id,name,phone,address,lat,lng',
                'rider:id,name,phone,availability_status,account_status,last_lat,last_lng,last_location_at',
            ])->withCount([
                'riderRequests as pending_rider_requests_count' => fn ($q) => $q->where('status', 'pending'),
                'riderRequests as total_rider_requests_count',
            ]);
            $this->applyFoodOrderFilters($query, $request);
        }
        if ($resource === 'medicine-orders') {
            $query->with([
                'items',
                'rider:id,name,phone,availability_status,account_status,last_lat,last_lng,last_location_at',
            ])->withCount([
                'riderRequests as pending_rider_requests_count' => fn ($q) => $q->where('status', 'pending'),
                'riderRequests as total_rider_requests_count',
            ]);
            $this->applyDeliveryOrderFilters($query, $request, false);
        }
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $this->applySearch($query, $model, $search);
        }

        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;
        $paginator = $query->latest()->paginate($perPage);
        if ($resource === 'food-orders') {
            $paginator->setCollection($paginator->getCollection()->map(fn (FoodOrder $order) => $this->decorateFoodOrder($order)));
        }
        if ($resource === 'medicine-orders') {
            $paginator->setCollection($paginator->getCollection()->map(fn (MedicineOrder $order) => $this->decorateMedicineOrder($order)));
        }

        $columns = $this->columnsFor($model);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'columns' => $columns,
        ]);
    }

    public function show(string $resource, int $id): JsonResponse
    {
        $model = $this->resolveModel($resource);
        if (! $model) {
            return response()->json(['message' => 'Resource not supported.'], 422);
        }

        $query = $model::query();
        if ($resource === 'food-orders') {
            $query->with([
                'items',
                'restaurant:id,name,phone,address,lat,lng',
                'address',
                'rider:id,name,phone,availability_status,account_status,last_lat,last_lng,last_location_at',
                'riderRequests.rider:id,name,phone,availability_status,account_status,last_lat,last_lng,last_location_at',
            ]);
            $query->withCount([
                'riderRequests as pending_rider_requests_count' => fn ($q) => $q->where('status', 'pending'),
                'riderRequests as total_rider_requests_count',
            ]);
        }
        if ($resource === 'riders') {
            $query->with(['documents', 'user:id,name,phone,email']);
        }
        if ($resource === 'medicine-orders') {
            $query->with([
                'items',
                'rider:id,name,phone,availability_status,account_status,last_lat,last_lng,last_location_at',
                'riderRequests.rider:id,name,phone,availability_status,account_status,last_lat,last_lng,last_location_at',
            ]);
            $query->withCount([
                'riderRequests as pending_rider_requests_count' => fn ($q) => $q->where('status', 'pending'),
                'riderRequests as total_rider_requests_count',
            ]);
        }

        $record = $query->findOrFail($id);

        if ($resource === 'food-orders') {
            $record = $this->decorateFoodOrder($record);
        }
        if ($resource === 'medicine-orders') {
            $record = $this->decorateMedicineOrder($record);
        }

        return response()->json($record);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $model = $this->resolveModel($resource);
        if (! $model) {
            return response()->json(['message' => 'Resource not supported.'], 422);
        }

        $payload = $request->except(['id', 'created_at', 'updated_at', 'deleted_at']);
        $record = $model::query()->create($payload);

        return response()->json(['message' => 'Created', 'record' => $record], 201);
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $model = $this->resolveModel($resource);
        if (! $model) {
            return response()->json(['message' => 'Resource not supported.'], 422);
        }

        $record = $model::query()->findOrFail($id);
        $payload = $request->except(['id', 'created_at', 'updated_at', 'deleted_at']);
        $record->fill($payload)->save();

        return response()->json(['message' => 'Updated', 'record' => $record]);
    }

    public function destroy(string $resource, int $id): JsonResponse
    {
        $model = $this->resolveModel($resource);
        if (! $model) {
            return response()->json(['message' => 'Resource not supported.'], 422);
        }

        $record = $model::query()->findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function foodOrderPaymentSummary(Request $request): JsonResponse
    {
        $base = FoodOrder::query()->with('restaurant:id,name,user_id,manual_bkash_number,manual_nagad_number');
        $this->applyFoodOrderFilters($base, $request);

        $orders = $base->get();
        $settings = FoodDeliverySetting::current();
        $deliveryFinancials = $this->deliveryFinancialTotals($orders, $settings);
        $ownerRows = $orders
            ->groupBy('restaurant_id')
            ->map(function ($rows) use ($settings) {
                $first = $rows->first();
                $manualRows = $rows->whereIn('payment_method', ['manual_bkash', 'manual_nagad']);
                $financials = $this->deliveryFinancialTotals($rows, $settings);

                return [
                    'restaurant_id' => $first->restaurant_id,
                    'restaurant_name' => $first->restaurant?->name ?: 'Unknown restaurant',
                    'owner_user_id' => $first->restaurant?->user_id,
                    'bkash_number' => $first->restaurant?->manual_bkash_number,
                    'nagad_number' => $first->restaurant?->manual_nagad_number,
                    'orders_count' => $rows->count(),
                    'cod_orders_count' => $rows->where('payment_method', 'cash_on_delivery')->count(),
                    'manual_orders_count' => $manualRows->count(),
                    'owner_received_total' => round((float) $manualRows->sum('grand_total'), 2),
                    'cod_collectable_total' => round((float) $rows->where('payment_method', 'cash_on_delivery')->sum('grand_total'), 2),
                    'delivery_fee_total' => round((float) $rows->sum('delivery_fee'), 2),
                    'rider_payout_total' => $financials['rider_payout_total'],
                    'admin_delivery_income_total' => $financials['admin_delivery_income_total'],
                    'grand_total' => round((float) $rows->sum('grand_total'), 2),
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        $methodRows = $orders
            ->groupBy('payment_method')
            ->map(fn ($rows, $method) => [
                'payment_method' => $method ?: 'unknown',
                'orders_count' => $rows->count(),
                'grand_total' => round((float) $rows->sum('grand_total'), 2),
                'delivery_fee_total' => round((float) $rows->sum('delivery_fee'), 2),
                'rider_payout_total' => $this->deliveryFinancialTotals($rows, $settings)['rider_payout_total'],
                'admin_delivery_income_total' => $this->deliveryFinancialTotals($rows, $settings)['admin_delivery_income_total'],
            ])
            ->values();

        return response()->json([
            'totals' => [
                'orders_count' => $orders->count(),
                'delivered_orders_count' => $orders->where('status', 'delivered')->count(),
                'grand_total' => round((float) $orders->sum('grand_total'), 2),
                'delivery_fee_total' => round((float) $orders->sum('delivery_fee'), 2),
                'rider_payout_total' => $deliveryFinancials['rider_payout_total'],
                'admin_delivery_income_total' => $deliveryFinancials['admin_delivery_income_total'],
                'owner_received_total' => round((float) $orders->whereIn('payment_method', ['manual_bkash', 'manual_nagad'])->sum('grand_total'), 2),
                'cod_collectable_total' => round((float) $orders->where('payment_method', 'cash_on_delivery')->sum('grand_total'), 2),
            ],
            'by_owner' => $ownerRows,
            'by_method' => $methodRows,
            'settings' => [
                'fixed_charge' => (float) ($settings->municipality_fixed_charge ?? $settings->fixed_charge ?? 50),
                'per_km_charge' => (float) ($settings->municipality_extra_per_km_charge ?? $settings->per_km_charge ?? 15),
                'rider_fixed_earning' => (float) ($settings->rider_fixed_earning ?? $settings->municipality_fixed_charge ?? 50),
                'rider_per_km_earning' => (float) ($settings->rider_per_km_earning ?? $settings->municipality_extra_per_km_charge ?? 15),
            ],
        ]);
    }

    public function deliveryIncomeSummary(Request $request): JsonResponse
    {
        $settings = FoodDeliverySetting::current();
        $foodQuery = FoodOrder::query();
        $this->applyFoodOrderFilters($foodQuery, $request);
        $medicineQuery = MedicineOrder::query();
        $this->applyDeliveryOrderFilters($medicineQuery, $request, false);

        $foodOrders = $foodQuery->get();
        $medicineOrders = $medicineQuery->get();
        $allOrders = $foodOrders->concat($medicineOrders);
        $food = $this->deliveryFinancialTotals($foodOrders, $settings);
        $medicine = $this->deliveryFinancialTotals($medicineOrders, $settings);
        $totals = $this->deliveryFinancialTotals($allOrders, $settings);
        $methodRows = $allOrders
            ->groupBy('payment_method')
            ->map(fn ($rows, $method) => [
                'payment_method' => $method ?: 'unknown',
                'orders_count' => $rows->count(),
                'grand_total' => round((float) $rows->sum('grand_total'), 2),
                ...$this->deliveryFinancialTotals($rows, $settings),
            ])
            ->values();

        return response()->json([
            'totals' => $totals + [
                'orders_count' => $allOrders->count(),
                'delivered_orders_count' => $allOrders->where('status', 'delivered')->count(),
                'grand_total' => round((float) $allOrders->sum('grand_total'), 2),
                'cash_collected_total' => round((float) $allOrders->sum('cash_collected'), 2),
            ],
            'by_service' => [
                [
                    'service_type' => 'food',
                    'orders_count' => $foodOrders->count(),
                    'delivered_orders_count' => $foodOrders->where('status', 'delivered')->count(),
                    ...$food,
                ],
                [
                    'service_type' => 'medicine',
                    'orders_count' => $medicineOrders->count(),
                    'delivered_orders_count' => $medicineOrders->where('status', 'delivered')->count(),
                    ...$medicine,
                ],
            ],
            'settings' => [
                'fixed_charge' => (float) ($settings->municipality_fixed_charge ?? $settings->fixed_charge ?? 50),
                'per_km_charge' => (float) ($settings->municipality_extra_per_km_charge ?? $settings->per_km_charge ?? 15),
                'rider_fixed_earning' => (float) ($settings->rider_fixed_earning ?? $settings->municipality_fixed_charge ?? 50),
                'rider_per_km_earning' => (float) ($settings->rider_per_km_earning ?? $settings->municipality_extra_per_km_charge ?? 15),
            ],
            'by_method' => $methodRows,
        ]);
    }

    private function resolveModel(string $resource): ?string
    {
        return self::RESOURCE_MAP[$resource] ?? null;
    }

    private function columnsFor(string $model): array
    {
        $table = (new $model)->getTable();
        if (! Schema::hasTable($table)) {
            return [];
        }
        return Schema::getColumnListing($table);
    }

    private function applySearch($query, string $model, string $search): void
    {
        $table = (new $model)->getTable();
        $candidates = ['name', 'title', 'phone', 'email', 'address', 'district', 'upazila', 'area', 'status', 'order_no', 'receiver_phone', 'operator_name', 'route_from', 'route_to', 'hotline', 'vehicle_number', 'kyc_status', 'account_status', 'availability_status', 'subject'];
        $columns = Schema::getColumnListing($table);
        $available = array_values(array_intersect($candidates, $columns));
        if (! $available) {
            return;
        }

        $query->where(function ($q) use ($available, $search) {
            foreach ($available as $column) {
                $q->orWhere($column, 'like', '%' . $search . '%');
            }
        });
    }

    private function applyFoodOrderFilters($query, Request $request): void
    {
        $this->applyDeliveryOrderFilters($query, $request, true);
    }

    private function applyDeliveryOrderFilters($query, Request $request, bool $hasRestaurant = true): void
    {
        $query
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->query('payment_method')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->query('payment_status')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($hasRestaurant && $request->filled('restaurant_id'), fn ($q) => $q->where('restaurant_id', (int) $request->query('restaurant_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('date_to')));
    }

    private function deliveryFinancialTotals($orders, FoodDeliverySetting $settings): array
    {
        $deliveryFee = 0;
        $riderPayout = 0;
        $adminIncome = 0;

        foreach ($orders as $order) {
            $fee = (float) ($order->delivery_fee ?? 0);
            $rider = (float) ($order->rider_earning ?? 0);
            if ($rider <= 0 && $fee > 0) {
                $rider = $this->estimateRiderEarning($order, $settings);
            }
            $admin = (float) ($order->admin_delivery_income ?? 0);
            if ($admin <= 0 && $fee > 0) {
                $admin = max(0, $fee - $rider);
            }

            $deliveryFee += $fee;
            $riderPayout += $rider;
            $adminIncome += $admin;
        }

        return [
            'delivery_fee_total' => round($deliveryFee, 2),
            'rider_payout_total' => round($riderPayout, 2),
            'admin_delivery_income_total' => round($adminIncome, 2),
        ];
    }

    private function estimateRiderEarning(FoodOrder|MedicineOrder $order, FoodDeliverySetting $settings): float
    {
        $deliveryFee = (float) ($order->delivery_fee ?? 0);
        $mode = (string) ($order->delivery_charge_mode ?? 'fixed');
        $fixedCharge = str_starts_with($mode, 'municipality')
            ? (float) ($settings->municipality_fixed_charge ?? $settings->fixed_charge ?? 50)
            : (float) ($settings->fixed_charge ?? $settings->base_charge ?? 0);
        $perKmCharge = str_starts_with($mode, 'municipality')
            ? (float) ($settings->municipality_extra_per_km_charge ?? 0)
            : (float) ($settings->per_km_charge ?? 0);
        $riderFixed = (float) ($settings->rider_fixed_earning ?? $fixedCharge);
        $riderPerKm = (float) ($settings->rider_per_km_earning ?? $perKmCharge);

        if (str_contains($mode, 'outside_per_km')) {
            $variableFee = max(0, $deliveryFee - $fixedCharge);
            $outsideKm = $perKmCharge > 0 ? $variableFee / $perKmCharge : 0;
            return round(min($deliveryFee, $riderFixed + ($outsideKm * $riderPerKm)), 2);
        }

        if ($mode === 'per_km') {
            $baseFee = min($deliveryFee, max($fixedCharge, (float) ($settings->base_charge ?? 0)));
            $variableFee = max(0, $deliveryFee - $baseFee);
            $variableKm = $perKmCharge > 0
                ? $variableFee / $perKmCharge
                : max(0, (float) ($order->delivery_distance_km ?? 0));
            return round(min($deliveryFee, $riderFixed + ($variableKm * $riderPerKm)), 2);
        }

        return round(min($deliveryFee, $riderFixed), 2);
    }

    private function decorateFoodOrder(FoodOrder $order): FoodOrder
    {
        $order->service_type = 'food';
        $order->rider_assignment_status = $order->rider_id ? 'accepted' : 'not_accepted';
        $order->rider_assignment_label = $order->rider_id
            ? 'Rider accepted'
            : (((int) ($order->pending_rider_requests_count ?? 0)) > 0 ? 'Waiting for rider' : 'No rider accepted yet');
        $order->accepted_rider_name = $order->rider?->name;
        $order->accepted_rider_phone = $order->rider?->phone;
        $order->route_distance_km = $this->foodOrderDistanceKm($order);
        $order->payment_proof_photo_url = $order->payment_proof_photo
            ? asset('storage/'.$order->payment_proof_photo)
            : null;

        return $order;
    }

    private function decorateMedicineOrder(MedicineOrder $order): MedicineOrder
    {
        $order->service_type = 'medicine';
        $order->rider_assignment_status = $order->rider_id ? 'accepted' : 'not_accepted';
        $order->rider_assignment_label = $order->rider_id
            ? 'Rider accepted'
            : (((int) ($order->pending_rider_requests_count ?? 0)) > 0 ? 'Waiting for rider' : 'No rider accepted yet');
        $order->accepted_rider_name = $order->rider?->name;
        $order->accepted_rider_phone = $order->rider?->phone;
        $order->route_distance_km = $order->delivery_distance_km !== null ? round((float) $order->delivery_distance_km, 2) : null;
        $order->payment_proof_photo_url = $order->payment_proof_photo
            ? asset('storage/'.$order->payment_proof_photo)
            : null;
        $order->delivery_proof_photo_url = $order->delivery_proof_photo
            ? asset('storage/'.$order->delivery_proof_photo)
            : null;

        return $order;
    }

    private function foodOrderDistanceKm(FoodOrder $order): ?float
    {
        if ($order->delivery_distance_km !== null) {
            return round((float) $order->delivery_distance_km, 2);
        }

        $restaurant = $order->restaurant;
        if (
            $restaurant?->lat === null
            || $restaurant?->lng === null
            || $order->delivery_lat === null
            || $order->delivery_lng === null
        ) {
            return null;
        }

        $earthKm = 6371;
        $fromLat = (float) $restaurant->lat;
        $fromLng = (float) $restaurant->lng;
        $toLat = (float) $order->delivery_lat;
        $toLng = (float) $order->delivery_lng;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return round($earthKm * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}

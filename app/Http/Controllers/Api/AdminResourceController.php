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
use App\Models\Hospital;
use App\Models\HospitalCategory;
use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Models\JobPost;
use App\Models\LaunchService;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceCategory;
use App\Models\Message;
use App\Models\News;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
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
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $this->applySearch($query, $model, $search);
        }

        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;
        $paginator = $query->latest()->paginate($perPage);

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

        $record = $model::query()->findOrFail($id);

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
        $candidates = ['name', 'title', 'phone', 'email', 'address', 'district', 'upazila', 'status', 'operator_name', 'route_from', 'route_to', 'hotline'];
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
}

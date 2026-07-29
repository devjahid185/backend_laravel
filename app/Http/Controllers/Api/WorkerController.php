<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use App\Models\WorkerCategory;
use App\Support\MediaLookup;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkerController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = WorkerCategory::query()
            ->withCount(['workers as workers_count' => fn ($q) => $q->where('status', 'approved')])
            ->orderBy('name')
            ->get(['id', 'name', 'icon']);

        return response()->json($categories);
    }

    public function index(Request $request): JsonResponse
    {
        $workers = DB::table('workers')
            ->join('users', 'users.id', '=', 'workers.user_id')
            ->join('worker_categories', 'worker_categories.id', '=', 'workers.category_id')
            ->select(
                'workers.*',
                'workers.name as worker_name',
                'workers.phone',
                'users.district',
                'users.upazila',
                'users.rating as user_rating',
                'worker_categories.name as category_name'
            )
            ->when($request->filled('category_id'), fn ($q) => $q->where('workers.category_id', $request->integer('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('users.district', $request->input('district')))
            ->where('workers.status', 'approved')
            ->orderByDesc('workers.id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $photoMap = MediaLookup::primaryUrlMap('worker', array_column($workers->items(), 'id'));

        $workers->setCollection(
            $workers->getCollection()->map(function ($worker) use ($photoMap) {
                $worker->worker_photo_url = $photoMap[$worker->id] ?? null;

                return $worker;
            })
        );

        return response()->json($workers);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $worker = DB::table('workers')
            ->join('users', 'users.id', '=', 'workers.user_id')
            ->join('worker_categories', 'worker_categories.id', '=', 'workers.category_id')
            ->select(
                'workers.*',
                'workers.name as worker_name',
                'workers.phone',
                'users.district',
                'users.upazila',
                'workers.address',
                'users.rating as user_rating',
                'worker_categories.name as category_name'
            )
            ->where('workers.id', $id)
            ->first();

        if (! $worker) {
            return response()->json(['message' => 'Worker not found'], 404);
        }

        $worker->worker_photo_url = MediaLookup::primaryUrlMap('worker', [$worker->id])[$worker->id] ?? null;
        $worker->is_owner = $worker->user_id === $request->user()->id;

        return response()->json($worker);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^\d{11}$/'],
            'category_id' => ['required', 'exists:worker_categories,id'],
            'experience' => ['nullable', 'integer', 'min:0'],
            'hourly_price' => ['nullable', 'numeric', 'min:0'],
            'skills' => ['nullable', 'string'],
            'service_area' => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        $worker = Worker::query()->create(
            $validated + ['status' => 'pending', 'user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Worker application submitted',
            'worker' => $worker,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $worker = Worker::query()->where('user_id', $request->user()->id)->first();

        if (! $worker) {
            return response()->json(['message' => 'Worker profile not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'regex:/^\d{11}$/'],
            'category_id' => ['sometimes', 'exists:worker_categories,id'],
            'experience' => ['sometimes', 'integer', 'min:0'],
            'hourly_price' => ['sometimes', 'numeric', 'min:0'],
            'skills' => ['sometimes', 'nullable', 'string'],
            'service_area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'availability' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $worker->update($validated);

        return response()->json([
            'message' => 'Worker profile updated',
            'worker' => $worker->fresh(),
        ]);
    }
}

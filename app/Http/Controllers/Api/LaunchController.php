<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaunchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 50;

        $launches = LaunchService::query()
            ->when($request->filled('route_from'), fn ($q) => $q->where('route_from', 'like', '%' . $request->input('route_from') . '%'))
            ->when($request->filled('route_to'), fn ($q) => $q->where('route_to', 'like', '%' . $request->input('route_to') . '%'))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('has_cabin'), fn ($q) => $q->where('has_cabin', $request->boolean('has_cabin')))
            ->when($request->filled('online_booking'), fn ($q) => $q->where('online_booking', $request->boolean('online_booking')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->input('q') . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('operator_name', 'like', $term)
                        ->orWhere('route_from', 'like', $term)
                        ->orWhere('route_to', 'like', $term)
                        ->orWhere('departure_terminal', 'like', $term)
                        ->orWhere('arrival_terminal', 'like', $term)
                        ->orWhere('hotline', 'like', $term);
                });
            })
            ->where('status', 'active')
            ->orderByRaw('departure_time is null, departure_time asc')
            ->orderByDesc('id')
            ->paginate($perPage);

        $launches->setCollection($launches->getCollection()->map(function (LaunchService $launch) use ($request) {
            $launch->is_owner = (int) $launch->user_id === (int) $request->user()->id;
            return $launch;
        }));

        return response()->json($launches);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $launch = LaunchService::query()->findOrFail($id);
        $launch->increment('views');
        $launch->is_owner = (int) $launch->user_id === (int) $request->user()->id;
        return response()->json($launch);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);
        $id = (int) $request->input('id', 0);

        if ($id > 0) {
            $launch = LaunchService::query()->where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
            $launch->fill($validated)->save();
        } else {
            $launch = LaunchService::query()->create($validated + ['user_id' => $request->user()->id]);
        }

        return response()->json(['message' => 'Launch information saved', 'launch' => $launch]);
    }

    public function myLaunches(Request $request): JsonResponse
    {
        return response()->json(LaunchService::query()->where('user_id', $request->user()->id)->orderByDesc('id')->get());
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'operator_name' => ['nullable', 'string', 'max:160'],
            'route_from' => ['nullable', 'string', 'max:120'],
            'route_to' => ['nullable', 'string', 'max:120'],
            'departure_terminal' => ['nullable', 'string', 'max:180'],
            'arrival_terminal' => ['nullable', 'string', 'max:180'],
            'departure_time' => ['nullable', 'date_format:H:i'],
            'arrival_time' => ['nullable', 'date_format:H:i'],
            'running_days' => ['nullable', 'string', 'max:180'],
            'deck_fare' => ['nullable', 'numeric', 'min:0'],
            'chair_fare' => ['nullable', 'numeric', 'min:0'],
            'single_cabin_fare' => ['nullable', 'numeric', 'min:0'],
            'double_cabin_fare' => ['nullable', 'numeric', 'min:0'],
            'has_cabin' => ['nullable', 'boolean'],
            'has_ac' => ['nullable', 'boolean'],
            'has_food' => ['nullable', 'boolean'],
            'online_booking' => ['nullable', 'boolean'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['string', 'max:30'],
            'hotline' => ['nullable', 'string', 'max:60'],
            'website' => ['nullable', 'string', 'max:180'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'pending'])],
        ]);
    }
}

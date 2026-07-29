<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloodRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = BloodRequest::query()
            ->when($request->filled('blood_group'), fn ($q) => $q->where('blood_group', $request->input('blood_group')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('location'), fn ($q) => $q->where('location', 'like', '%'.$request->input('location').'%'))
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $requests->setCollection(
            $requests->getCollection()->map(function (BloodRequest $req) use ($request) {
                $req->is_owner = $request->user()->id === $req->user_id;

                return $req;
            })
        );

        return response()->json($requests);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $req = BloodRequest::query()->findOrFail($id);
        $req->is_owner = $request->user()->id === $req->user_id;

        return response()->json($req);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_name' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['required', 'string', 'max:5'],
            'units' => ['nullable', 'integer', 'min:1', 'max:10'],
            'needed_at' => ['nullable', 'date'],
            'hospital' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string'],
        ]);

        $req = BloodRequest::query()->create(array_merge($validated, [
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]));

        return response()->json([
            'message' => 'Blood request created',
            'request' => $req,
        ]);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $req = BloodRequest::query()->findOrFail($id);

        if ($req->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $req->update(['status' => 'closed']);

        return response()->json(['message' => 'Request closed']);
    }
}

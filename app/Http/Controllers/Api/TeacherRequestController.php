<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherRequest;
use App\Models\TeacherCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = TeacherRequest::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('medium'), fn ($q) => $q->where('medium', $request->input('medium')))
            ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->input('mode')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('title', 'like', $term)
                    ->orWhere('class_level', 'like', $term)
                    ->orWhere('address', 'like', $term);
            }))
            ->orderByDesc('id')
            ->paginate(20);

        $categoryMap = TeacherCategory::query()->pluck('name', 'id')->all();
        $requests->setCollection(
            $requests->getCollection()->map(function (TeacherRequest $req) use ($categoryMap, $request) {
                $req->category_name = $req->category_id ? ($categoryMap[$req->category_id] ?? null) : null;
                $req->is_owner = (int) $req->user_id === (int) $request->user()->id;

                return $req;
            })
        );

        return response()->json($requests);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $req = TeacherRequest::query()->findOrFail($id);
        $req->increment('views');
        $req->category_name = $req->category_id
            ? TeacherCategory::query()->find($req->category_id)?->name
            : null;
        $req->is_owner = (int) $req->user_id === (int) $request->user()->id;

        return response()->json($req);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:teacher_categories,id'],
            'title' => ['required', 'string', 'max:160'],
            'class_level' => ['nullable', 'string', 'max:80'],
            'medium' => ['nullable', 'string', 'max:60'],
            'mode' => ['nullable', 'string', 'max:40'],
            'days_per_week' => ['nullable', 'string', 'max:40'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $req = TeacherRequest::query()->create($validated + [
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Teacher request created',
            'request' => $req,
        ]);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $req = TeacherRequest::query()->findOrFail($id);
        if ((int) $req->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $req->update(['status' => 'closed']);

        return response()->json(['message' => 'Request closed']);
    }

    public function myRequests(Request $request): JsonResponse
    {
        $requests = TeacherRequest::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($requests);
    }
}

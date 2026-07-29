<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EducationCategory;
use App\Models\EducationInstitute;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(EducationCategory::query()->orderBy('name')->get());
    }

    public function index(Request $request): JsonResponse
    {
        $institutes = EducationInstitute::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('upazila'), fn ($q) => $q->where('upazila', $request->input('upazila')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('district', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $map = MediaLookup::primaryUrlMap('education', array_column($institutes->items(), 'id'));
        $institutes->setCollection(
            $institutes->getCollection()->map(function (EducationInstitute $institute) use ($map, $request) {
                $institute->image_url = $map[$institute->id] ?? null;
                $institute->is_owner = (int) $institute->user_id === (int) $request->user()->id;
                $institute->category_name = $institute->category_id
                    ? EducationCategory::query()->find($institute->category_id)?->name
                    : null;

                return $institute;
            })
        );

        return response()->json($institutes);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $institute = EducationInstitute::query()->findOrFail($id);
        $institute->increment('views');
        $institute->image_url = MediaLookup::primaryUrlMap('education', [$institute->id])[$institute->id] ?? null;
        $institute->is_owner = (int) $institute->user_id === (int) $request->user()->id;
        $institute->category_name = $institute->category_id
            ? EducationCategory::query()->find($institute->category_id)?->name
            : null;

        return response()->json($institute);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:education_categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'max:80'],
            'eiin' => ['nullable', 'string', 'max:20'],
            'board' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'levels' => ['nullable', 'array'],
            'levels.*' => ['string', 'max:120'],
            'mediums' => ['nullable', 'array'],
            'mediums.*' => ['string', 'max:120'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'max:120'],
            'description' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $institute = EducationInstitute::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Education institute saved',
            'institute' => $institute,
        ]);
    }

    public function myInstitutes(Request $request): JsonResponse
    {
        $institutes = EducationInstitute::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($institutes);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherCategory;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(TeacherCategory::query()->orderBy('name')->get());
    }

    public function index(Request $request): JsonResponse
    {
        $teachers = Teacher::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('medium'), fn ($q) => $q->where('medium', $request->input('medium')))
            ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->input('mode')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('education', 'like', $term)
                    ->orWhere('institute', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $map = MediaLookup::primaryUrlMap('teacher', array_column($teachers->items(), 'id'));
        $teachers->setCollection(
            $teachers->getCollection()->map(function (Teacher $teacher) use ($map, $request) {
                $teacher->image_url = $map[$teacher->id] ?? null;
                $teacher->is_owner = (int) $teacher->user_id === (int) $request->user()->id;
                $teacher->category_name = $teacher->category_id
                    ? TeacherCategory::query()->find($teacher->category_id)?->name
                    : null;

                return $teacher;
            })
        );

        return response()->json($teachers);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = Teacher::query()->findOrFail($id);
        $teacher->increment('views');
        $teacher->image_url = MediaLookup::primaryUrlMap('teacher', [$teacher->id])[$teacher->id] ?? null;
        $teacher->is_owner = (int) $teacher->user_id === (int) $request->user()->id;
        $teacher->category_name = $teacher->category_id
            ? TeacherCategory::query()->find($teacher->category_id)?->name
            : null;

        return response()->json($teacher);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:teacher_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['string', 'max:120'],
            'class_levels' => ['nullable', 'array'],
            'class_levels.*' => ['string', 'max:80'],
            'medium' => ['nullable', 'string', 'max:60'],
            'gender' => ['nullable', 'string', 'max:20'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'education' => ['nullable', 'string', 'max:180'],
            'institute' => ['nullable', 'string', 'max:160'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'monthly_rate' => ['nullable', 'numeric', 'min:0'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'preferred_area' => ['nullable', 'string', 'max:255'],
            'mode' => ['nullable', 'string', 'max:40'],
            'availability' => ['nullable', 'string'],
            'about' => ['nullable', 'string'],
            'is_available' => ['nullable', 'boolean'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $teacher = Teacher::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Teacher profile saved',
            'teacher' => $teacher,
        ]);
    }

    public function availability(Request $request, int $id): JsonResponse
    {
        $teacher = Teacher::query()->findOrFail($id);
        if ((int) $teacher->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate(['is_available' => ['required', 'boolean']]);
        $teacher->update(['is_available' => (bool) $validated['is_available']]);

        return response()->json(['message' => 'Availability updated']);
    }

    public function myTeachers(Request $request): JsonResponse
    {
        $teachers = Teacher::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($teachers);
    }
}

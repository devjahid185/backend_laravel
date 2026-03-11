<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorCategory;
use App\Models\DoctorSchedule;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(DoctorCategory::query()->orderBy('name')->get());
    }

    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('specialization', 'like', $term)
                    ->orWhere('hospital', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate(20);

        $map = MediaLookup::primaryUrlMap('doctor', array_column($doctors->items(), 'id'));
        $doctors->setCollection(
            $doctors->getCollection()->map(function (Doctor $doctor) use ($map, $request) {
                $doctor->image_url = $map[$doctor->id] ?? null;
                $doctor->is_owner = (int) $doctor->user_id === (int) $request->user()->id;
                $doctor->category_name = $doctor->category_id
                    ? DoctorCategory::query()->find($doctor->category_id)?->name
                    : null;

                return $doctor;
            })
        );

        return response()->json($doctors);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $doctor = Doctor::query()->findOrFail($id);
        $doctor->increment('views');
        $doctor->image_url = MediaLookup::primaryUrlMap('doctor', [$doctor->id])[$doctor->id] ?? null;
        $doctor->is_owner = (int) $doctor->user_id === (int) $request->user()->id;
        $doctor->category_name = $doctor->category_id
            ? DoctorCategory::query()->find($doctor->category_id)?->name
            : null;
        $doctor->schedules = DoctorSchedule::query()->where('doctor_id', $doctor->id)->get();

        return response()->json($doctor);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:doctor_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'specialization' => ['nullable', 'string', 'max:120'],
            'hospital' => ['nullable', 'string', 'max:120'],
            'clinic' => ['nullable', 'string', 'max:120'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'degrees' => ['nullable', 'string', 'max:255'],
            'bmdc_number' => ['nullable', 'string', 'max:80'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'chamber_time' => ['nullable', 'string', 'max:120'],
            'about' => ['nullable', 'string'],
            'is_available' => ['nullable', 'boolean'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $doctor = Doctor::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Doctor profile saved',
            'doctor' => $doctor,
        ]);
    }

    public function setSchedules(Request $request, int $id): JsonResponse
    {
        $doctor = Doctor::query()->findOrFail($id);
        if ((int) $doctor->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'schedules' => ['nullable', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'string'],
            'schedules.*.start_time' => ['nullable', 'string'],
            'schedules.*.end_time' => ['nullable', 'string'],
            'schedules.*.note' => ['nullable', 'string', 'max:120'],
        ]);

        DoctorSchedule::query()->where('doctor_id', $doctor->id)->delete();
        if (! empty($validated['schedules'])) {
            foreach ($validated['schedules'] as $slot) {
                DoctorSchedule::query()->create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => $slot['start_time'] ?? null,
                    'end_time' => $slot['end_time'] ?? null,
                    'note' => $slot['note'] ?? null,
                ]);
            }
        }

        return response()->json(['message' => 'Schedule updated']);
    }

    public function availability(Request $request, int $id): JsonResponse
    {
        $doctor = Doctor::query()->findOrFail($id);
        if ((int) $doctor->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate(['is_available' => ['required', 'boolean']]);
        $doctor->update(['is_available' => (bool) $validated['is_available']]);

        return response()->json(['message' => 'Availability updated']);
    }
}

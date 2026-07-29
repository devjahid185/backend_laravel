<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourierCompany;
use App\Models\CourierOffice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    public function companies(Request $request): JsonResponse
    {
        $companies = CourierCompany::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (CourierCompany $company) {
                $company->offices_count = CourierOffice::query()
                    ->where('company_id', $company->id)
                    ->count();

                return $company;
            });

        return response()->json($companies);
    }

    public function offices(Request $request): JsonResponse
    {
        $offices = CourierOffice::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', (int) $request->input('company_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('address', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderBy('name')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $companyMap = CourierCompany::query()->pluck('name', 'id')->all();
        $offices->setCollection(
            $offices->getCollection()->map(function (CourierOffice $office) use ($companyMap, $request) {
                $office->company_name = $companyMap[$office->company_id] ?? null;
                $office->is_owner = $office->user_id && (int) $office->user_id === (int) $request->user()->id;

                return $office;
            })
        );

        return response()->json($offices);
    }

    public function companyShow(int $id): JsonResponse
    {
        $company = CourierCompany::query()->findOrFail($id);
        $company->increment('views');
        $company->offices = CourierOffice::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return response()->json($company);
    }

    public function officeShow(Request $request, int $id): JsonResponse
    {
        $office = CourierOffice::query()->findOrFail($id);
        $office->increment('views');
        $office->company_name = CourierCompany::query()->find($office->company_id)?->name;
        $office->is_owner = $office->user_id && (int) $office->user_id === (int) $request->user()->id;

        return response()->json($office);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'branch_name' => ['required', 'string', 'max:160'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['string', 'max:30'],
            'hotline' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:120'],
            'notes' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $company = CourierCompany::query()->firstOrCreate(
            ['name' => $validated['company_name']],
            [
                'user_id' => $request->user()->id,
                'website' => $validated['website'] ?? null,
                'facebook' => $validated['facebook'] ?? null,
                'hotline' => $validated['hotline'] ?? null,
                'email' => $validated['email'] ?? null,
            ]
        );

        $office = CourierOffice::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => $validated['branch_name'],
            ],
            [
                'user_id' => $request->user()->id,
                'district' => $validated['district'] ?? null,
                'upazila' => $validated['upazila'] ?? null,
                'address' => $validated['address'] ?? null,
                'phones' => $validated['phones'] ?? null,
                'hotline' => $validated['hotline'] ?? null,
                'email' => $validated['email'] ?? null,
                'website' => $validated['website'] ?? null,
                'facebook' => $validated['facebook'] ?? null,
                'services' => $validated['services'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Courier saved',
            'office' => $office,
        ]);
    }

    public function myOffices(Request $request): JsonResponse
    {
        $offices = CourierOffice::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($offices);
    }
}

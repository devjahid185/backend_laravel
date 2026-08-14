<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeServiceShortcut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeServiceShortcutController extends Controller
{
    public function active(): JsonResponse
    {
        return response()->json(
            HomeServiceShortcut::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = HomeServiceShortcut::query()
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%' . $request->query('search') . '%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', $term)
                        ->orWhere('subtitle', 'like', $term)
                        ->orWhere('endpoint', 'like', $term);
                });
            });

        return response()->json($query->orderBy('sort_order')->orderBy('id')->paginate(
            (int) min(max((int) $request->query('per_page', 50), 1), 100)
        ));
    }

    public function adminStore(Request $request): JsonResponse
    {
        $shortcut = HomeServiceShortcut::query()->create($this->validated($request));

        return response()->json([
            'message' => 'Shortcut created.',
            'record' => $shortcut,
        ], 201);
    }

    public function adminUpdate(Request $request, int $id): JsonResponse
    {
        $shortcut = HomeServiceShortcut::query()->findOrFail($id);
        $shortcut->fill($this->validated($request, $id))->save();

        return response()->json([
            'message' => 'Shortcut updated.',
            'record' => $shortcut->fresh(),
        ]);
    }

    public function adminDestroy(int $id): JsonResponse
    {
        HomeServiceShortcut::query()->findOrFail($id)->delete();

        return response()->json(['message' => 'Shortcut deleted.']);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'endpoint' => ['required', 'string', 'max:180', 'unique:home_service_shortcuts,endpoint,' . ($id ?: 'NULL') . ',id'],
            'icon' => ['nullable', 'string', 'max:80'],
            'accent_color' => ['nullable', 'string', 'max:24'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}

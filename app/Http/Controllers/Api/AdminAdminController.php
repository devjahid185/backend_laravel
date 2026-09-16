<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminAdminController extends Controller
{
    private const ACTIONS = ['view', 'create', 'update', 'delete'];

    public function index(Request $request): JsonResponse
    {
        $this->ensureSuper($request);
        $admins = Admin::query()->orderByDesc('id')->get();
        return response()->json([
            'admins' => $admins,
            'permission_catalog' => $this->permissionCatalog(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureSuper($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_super' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ]);

        $admin = Admin::query()->create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'is_super' => (bool) ($validated['is_super'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'permissions' => $this->cleanPermissions($validated['permissions'] ?? []),
        ]);

        return response()->json([
            'message' => 'Admin created.',
            'admin' => $admin,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->ensureSuper($request);
        $admin = Admin::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'is_super' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ]);

        if ($admin->id === $request->user()?->id && array_key_exists('is_super', $validated) && ! $validated['is_super']) {
            abort(422, 'You cannot remove your own full admin access.');
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        if (array_key_exists('permissions', $validated)) {
            $validated['permissions'] = $this->cleanPermissions($validated['permissions'] ?? []);
        }

        $admin->update($validated);

        return response()->json([
            'message' => 'Admin updated.',
            'admin' => $admin->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ensureSuper($request);
        $admin = Admin::query()->findOrFail($id);
        abort_if($admin->id === $request->user()?->id, 422, 'You cannot delete your own admin account.');
        $admin->delete();

        return response()->json(['message' => 'Admin deleted.']);
    }

    private function ensureSuper(Request $request): void
    {
        abort_unless($request->user() instanceof Admin && $request->user()->is_super, 403, 'Full admin access is required.');
    }

    private function permissionCatalog(): array
    {
        return AdminModule::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['name', 'slug', 'group_name'])
            ->map(fn (AdminModule $module): array => [
                'name' => $module->name,
                'slug' => $module->slug,
                'group_name' => $module->group_name ?: 'General',
                'actions' => $module->slug === 'dashboard' ? ['view'] : self::ACTIONS,
            ])
            ->values()
            ->all();
    }

    private function cleanPermissions(array $permissions): array
    {
        $validSlugs = AdminModule::query()->where('is_active', true)->pluck('slug')->all();
        $validActions = self::ACTIONS;
        $clean = [];

        foreach ($permissions as $slug => $actions) {
            if (! in_array($slug, $validSlugs, true) || ! is_array($actions)) {
                continue;
            }
            $allowed = array_values(array_intersect($validActions, $actions));
            if ($allowed) {
                $clean[$slug] = $allowed;
            }
        }

        return $clean;
    }
}

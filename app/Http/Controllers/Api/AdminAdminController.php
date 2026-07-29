<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminAdminController extends Controller
{
    public function index(): JsonResponse
    {
        $admins = Admin::query()->orderByDesc('id')->get();
        return response()->json(['admins' => $admins]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_super' => ['sometimes', 'boolean'],
        ]);

        $admin = Admin::query()->create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Admin created.',
            'admin' => $admin,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $admin = Admin::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'is_super' => ['sometimes', 'boolean'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $admin->update($validated);

        return response()->json([
            'message' => 'Admin updated.',
            'admin' => $admin->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $admin = Admin::query()->findOrFail($id);
        $admin->delete();

        return response()->json(['message' => 'Admin deleted.']);
    }
}

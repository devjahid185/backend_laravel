<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;
        $users = $query->orderByDesc('id')->paginate($perPage);

        return response()->json($users);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')],
            'role' => ['required', Rule::in(['user', 'worker', 'admin', 'business'])],
            'password' => ['required', 'string', 'min:6'],
            'verified' => ['sometimes', 'boolean'],
            'is_blocked' => ['sometimes', 'boolean'],
        ]);

        $user = User::query()->create($validated);

        return response()->json([
            'message' => 'User created.',
            'user' => $user,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['user', 'worker', 'admin', 'business'])],
            'verified' => ['sometimes', 'boolean'],
            'is_blocked' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated.',
            'user' => $user->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}

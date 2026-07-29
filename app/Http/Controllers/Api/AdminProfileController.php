<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($admin);
    }

    public function update(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $admin->update($validated);

        return response()->json([
            'message' => 'Profile updated.',
            'admin' => $admin->fresh(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::query()->where('email', $validated['email'])->first();
        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'credentials' => ['Invalid credentials.'],
            ]);
        }

        $admin->update(['last_login_at' => now()]);
        $token = $admin->createToken('admin-panel', ['admin'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'admin' => $admin,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            $token = $request->bearerToken();
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                $tokenable = $accessToken?->tokenable;
                if ($tokenable instanceof Admin) {
                    $admin = $tokenable;
                }
            }
        }

        if (! $admin instanceof Admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($admin);
    }

    public function logout(Request $request): JsonResponse
    {
        $admin = $request->user();
        if ($admin instanceof Admin) {
            $admin->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}

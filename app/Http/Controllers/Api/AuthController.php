<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'district' => ['nullable', 'string', 'max:255'],
            'upazila' => ['nullable', 'string', 'max:255'],
            'union_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:user,worker,admin,business'],
        ]);

        $user = User::query()->create($validated);
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required_without:email', 'string', 'max:20'],
            'email' => ['required_without:phone', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->when($request->filled('phone'), fn ($q) => $q->where('phone', $validated['phone']))
            ->when($request->filled('email'), fn ($q) => $q->where('email', $validated['email']))
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'credentials' => ['Invalid credentials.'],
            ]);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => 'Your account is blocked.'], 403);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();

        return response()->json([
            ...$user->toArray(),
            'photo_url' => MediaUrl::toUrl($user->photo),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,'.$request->user()->id],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
            'photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'district' => ['sometimes', 'nullable', 'string', 'max:255'],
            'upazila' => ['sometimes', 'nullable', 'string', 'max:255'],
            'union_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
        ]);

        if ($request->hasFile('photo')) {
            if ($request->user()->photo) {
                Storage::disk('public')->delete($request->user()->photo);
            }

            $validated['photo'] = $request->file('photo')->store(
                'uploads/profile/'.date('Y/m'),
                'public'
            );
        } elseif (array_key_exists('photo_path', $validated)) {
            $validated['photo'] = $validated['photo_path'];
        }

        unset($validated['photo_path']);
        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $request->user()->update($validated);
        $user = $request->user()->fresh();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                ...$user->toArray(),
                'photo_url' => MediaUrl::toUrl($user->photo),
            ],
        ]);
    }
}

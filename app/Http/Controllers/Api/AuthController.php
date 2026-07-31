<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Services\SmsService;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function requestOtp(Request $request, SmsService $sms): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'purpose' => ['required', 'in:register,reset,login'],
        ]);

        $userExists = User::query()->where('phone', $validated['phone'])->exists();
        if (in_array($validated['purpose'], ['reset', 'login'], true) && ! $userExists) {
            throw ValidationException::withMessages([
                'phone' => ['No account found with this phone number.'],
            ]);
        }

        if ($validated['purpose'] === 'register' && $userExists) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number is already registered.'],
            ]);
        }

        $cooldownKey = $this->otpThrottleKey('otp-request-cooldown', $validated['phone'], $validated['purpose'], $request->ip());
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return response()->json([
                'message' => 'Please wait '.RateLimiter::availableIn($cooldownKey).' seconds before requesting another OTP.',
            ], 429);
        }

        $burstKey = $this->otpThrottleKey('otp-request-burst', $validated['phone'], $validated['purpose'], $request->ip());
        if (RateLimiter::tooManyAttempts($burstKey, 5)) {
            return response()->json([
                'message' => 'Too many OTP requests. Please try again after '.ceil(RateLimiter::availableIn($burstKey) / 60).' minutes.',
            ], 429);
        }

        RateLimiter::hit($cooldownKey, 60);
        RateLimiter::hit($burstKey, 15 * 60);

        PhoneOtp::query()
            ->where('phone', $validated['phone'])
            ->where('purpose', $validated['purpose'])
            ->delete();

        $code = (string) random_int(100000, 999999);
        $otp = PhoneOtp::query()->create([
            'phone' => $validated['phone'],
            'code' => $code,
            'purpose' => $validated['purpose'],
            'expires_at' => now()->addMinutes(5),
        ]);
        Log::info("Generated OTP for {$validated['phone']} ({$validated['purpose']}): $code");

        try {
            $sms->sendOtp($validated['phone'], $code);
        } catch (\Throwable $e) {
            Log::error('OTP SMS failed', [
                'phone' => $this->maskPhone($validated['phone']),
                'purpose' => $validated['purpose'],
                'error' => $e->getMessage(),
            ]);

            $message = 'OTP পাঠাতে সমস্যা হয়েছে। একটু পরে আবার চেষ্টা করুন।';
            if (config('app.debug')) {
                $message = $message.' ('.$e->getMessage().')';
            }

            return response()->json(['message' => $message], 500);
        }

        $response = [
            'message' => 'OTP sent to your phone',
            'expires_at' => $otp->expires_at,
        ];

        if (config('app.debug')) {
            $response['otp'] = $code;
        }

        return response()->json($response);
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 4) {
            return $digits;
        }

        $start = substr($digits, 0, 2);
        $end = substr($digits, -2);

        return $start . str_repeat('*', max(0, strlen($digits) - 4)) . $end;
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'purpose' => ['required', 'in:register,reset,login'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $otp = $validated['purpose'] === 'reset'
            ? $this->verifyOtpWithoutConsuming($validated['phone'], $validated['purpose'], $validated['otp'])
            : $this->consumeOtp($validated['phone'], $validated['purpose'], $validated['otp']);

        return response()->json([
            'message' => 'OTP verified',
            'verified_at' => $validated['purpose'] === 'reset' ? now() : $otp->consumed_at,
        ]);
    }

    public function registerWithOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'district' => ['nullable', 'string', 'max:255'],
            'upazila' => ['nullable', 'string', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $this->consumeOtp($validated['phone'], 'register', $validated['otp']);

        $user = User::query()->create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function resetPasswordWithOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $this->consumeOtp($validated['phone'], 'reset', $validated['otp']);

        $user = User::query()->where('phone', $validated['phone'])->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['User not found.'],
            ]);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password reset successful']);
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

    public function loginGoogle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $clientIds = $this->googleClientIds();
        if (empty($clientIds)) {
            return response()->json(['message' => 'Google login not configured.'], 500);
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $validated['id_token'],
        ]);

        if (! $response->ok()) {
            return response()->json(['message' => 'Invalid Google token.'], 401);
        }

        $data = $response->json();
        $aud = $data['aud'] ?? null;
        if (! $aud || ! in_array($aud, $clientIds, true)) {
            return response()->json(['message' => 'Google token audience mismatch.'], 401);
        }

        $googleId = $data['sub'] ?? null;
        $email = $data['email'] ?? null;
        $name = $data['name'] ?? 'Google User';
        $picture = $data['picture'] ?? null;
        $emailVerified = filter_var($data['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $googleId || ! $email) {
            return response()->json(['message' => 'Google account data missing.'], 422);
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => null,
                'google_id' => $googleId,
                'password' => Hash::make(Str::random(40)),
                'photo' => $picture,
                'email_verified_at' => $emailVerified ? now() : null,
                'verified' => $emailVerified,
            ]);
        } else {
            $user->google_id = $user->google_id ?: $googleId;
            if ($picture && empty($user->photo)) {
                $user->photo = $picture;
            }
            if ($emailVerified && ! $user->email_verified_at) {
                $user->email_verified_at = now();
                $user->verified = true;
            }
            $user->save();
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

    private function consumeOtp(string $phone, string $purpose, string $code): PhoneOtp
    {
        $this->ensureOtpAttemptsAllowed($phone, $purpose);

        $otp = PhoneOtp::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        if (! $otp || $otp->code !== $code) {
            $this->hitOtpAttempt($phone, $purpose);
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        if ($otp->expires_at->isPast()) {
            $this->hitOtpAttempt($phone, $purpose);
            throw ValidationException::withMessages([
                'otp' => ['OTP expired.'],
            ]);
        }

        $otp->update(['consumed_at' => Carbon::now()]);
        RateLimiter::clear($this->otpThrottleKey('otp-verify', $phone, $purpose));

        return $otp;
    }

    private function verifyOtpWithoutConsuming(string $phone, string $purpose, string $code): PhoneOtp
    {
        $this->ensureOtpAttemptsAllowed($phone, $purpose);

        $otp = PhoneOtp::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        if (! $otp || $otp->code !== $code) {
            $this->hitOtpAttempt($phone, $purpose);
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        if ($otp->expires_at->isPast()) {
            $this->hitOtpAttempt($phone, $purpose);
            throw ValidationException::withMessages([
                'otp' => ['OTP expired.'],
            ]);
        }

        RateLimiter::clear($this->otpThrottleKey('otp-verify', $phone, $purpose));

        return $otp;
    }

    private function ensureOtpAttemptsAllowed(string $phone, string $purpose): void
    {
        $key = $this->otpThrottleKey('otp-verify', $phone, $purpose);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'otp' => ['Too many invalid attempts. Please request a new OTP after '.ceil(RateLimiter::availableIn($key) / 60).' minutes.'],
            ]);
        }
    }

    private function hitOtpAttempt(string $phone, string $purpose): void
    {
        RateLimiter::hit($this->otpThrottleKey('otp-verify', $phone, $purpose), 10 * 60);
    }

    private function otpThrottleKey(string $prefix, string $phone, string $purpose, ?string $ip = null): string
    {
        return $prefix.':'.sha1($purpose.'|'.$phone.'|'.($ip ?? ''));
    }

    private function googleClientIds(): array
    {
        $raw = (string) config('services.google.client_ids');
        $list = array_filter(array_map('trim', explode(',', $raw)));
        return array_values($list);
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

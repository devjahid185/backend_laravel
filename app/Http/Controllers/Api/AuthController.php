<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Services\AuthEmailService;
use App\Services\SmsService;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, AuthEmailService $email): JsonResponse
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
        $token = $user->createToken($this->deviceTokenName($request))->plainTextToken;
        $this->sendWelcomeEmail($user, $email);

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
            'purpose' => ['required', 'in:register,reset,login,password_change'],
        ]);

        if ($validated['purpose'] === 'password_change' && ! $request->user()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $userExists = User::query()->where('phone', $validated['phone'])->exists();
        if (in_array($validated['purpose'], ['reset', 'login', 'password_change'], true) && ! $userExists) {
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
            'purpose' => ['required', 'in:register,reset,login,password_change'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if ($validated['purpose'] === 'password_change' && ! $request->user()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $otp = in_array($validated['purpose'], ['reset', 'password_change'], true)
            ? $this->verifyOtpWithoutConsuming($validated['phone'], $validated['purpose'], $validated['otp'])
            : $this->consumeOtp($validated['phone'], $validated['purpose'], $validated['otp']);

        return response()->json([
            'message' => 'OTP verified',
            'verified_at' => in_array($validated['purpose'], ['reset', 'password_change'], true) ? now() : $otp->consumed_at,
        ]);
    }

    public function registerWithOtp(Request $request, AuthEmailService $email): JsonResponse
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
        unset($validated['otp']);

        $user = User::query()->create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken($this->deviceTokenName($request))->plainTextToken;
        $this->sendWelcomeEmail($user, $email);

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

    public function requestEmailPasswordReset(Request $request, AuthEmailService $email): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'exists:users,email'],
        ]);

        $resetEmail = Str::lower($validated['email']);
        $cooldownKey = 'email-reset-cooldown:'.sha1($resetEmail.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return response()->json([
                'message' => 'নতুন কোড পাঠানোর আগে '.RateLimiter::availableIn($cooldownKey).' সেকেন্ড অপেক্ষা করুন।',
            ], 429);
        }

        $burstKey = 'email-reset-burst:'.sha1($resetEmail.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($burstKey, 5)) {
            return response()->json([
                'message' => 'অনেকবার চেষ্টা করা হয়েছে। '.ceil(RateLimiter::availableIn($burstKey) / 60).' মিনিট পরে আবার চেষ্টা করুন।',
            ], 429);
        }

        $user = User::query()->where('email', $resetEmail)->firstOrFail();
        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $resetEmail],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        try {
            $email->sendPasswordResetCode($user, $code);
        } catch (\Throwable $e) {
            Log::error('Password reset email failed', [
                'email' => $this->maskEmail($resetEmail),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'ইমেইল পাঠাতে সমস্যা হয়েছে। মেইল সেটিংস দেখে আবার চেষ্টা করুন।',
            ], 500);
        }

        RateLimiter::hit($cooldownKey, 60);
        RateLimiter::hit($burstKey, 15 * 60);

        return response()->json(['message' => 'পাসওয়ার্ড রিসেট কোড ইমেইলে পাঠানো হয়েছে।']);
    }

    public function resetPasswordWithEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $resetEmail = Str::lower($validated['email']);
        $this->ensureEmailResetCodeValid($resetEmail, $validated['otp']);

        $user = User::query()->where('email', $resetEmail)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['এই ইমেইলে কোনো অ্যাকাউন্ট পাওয়া যায়নি।'],
            ]);
        }

        $user->update(['password' => Hash::make($validated['password'])]);
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $resetEmail)->delete();

        return response()->json(['message' => 'পাসওয়ার্ড সফলভাবে রিসেট হয়েছে।']);
    }

    public function verifyEmailPasswordReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $this->ensureEmailResetCodeValid(Str::lower($validated['email']), $validated['otp']);

        return response()->json([
            'message' => 'রিসেট কোড যাচাই হয়েছে।',
            'verified_at' => now(),
        ]);
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

        $token = $user->createToken($this->deviceTokenName($request))->plainTextToken;

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

        $token = $user->createToken($this->deviceTokenName($request))->plainTextToken;

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

    private function deviceTokenName(Request $request): string
    {
        $platform = trim((string) $request->input('device_platform', 'mobile'));
        $name = trim((string) $request->input('device_name', 'Bholavashi App'));

        return Str::limit($name.' · '.$platform, 120, '');
    }

    private function sendWelcomeEmail(User $user, AuthEmailService $email): void
    {
        if (! $user->email) {
            return;
        }

        try {
            $email->sendWelcome($user);
        } catch (\Throwable $e) {
            Log::warning('Welcome email skipped', [
                'user_id' => $user->id,
                'email' => $this->maskEmail($user->email),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $maskedName = strlen($name) <= 2
            ? str_repeat('*', strlen($name))
            : substr($name, 0, 1).str_repeat('*', strlen($name) - 2).substr($name, -1);

        return $maskedName.'@'.$domain;
    }

    private function ensureEmailResetCodeValid(string $email, string $code): void
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $record || ! Hash::check($code, $record->token)) {
            throw ValidationException::withMessages([
                'otp' => ['রিসেট কোড সঠিক নয়।'],
            ]);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'otp' => ['রিসেট কোডের সময় শেষ হয়েছে। নতুন কোড নিন।'],
            ]);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function requestPasswordChangeOtp(Request $request, SmsService $sms): JsonResponse
    {
        $user = $request->user();
        if (! $user->phone) {
            return response()->json([
                'message' => 'এই অ্যাকাউন্টে ফোন নম্বর নেই। আগে প্রোফাইলে ফোন নম্বর যোগ করুন।',
            ], 422);
        }

        $request->merge([
            'phone' => $user->phone,
            'purpose' => 'password_change',
        ]);

        return $this->requestOtp($request, $sms);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = $request->user();
        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['বর্তমান পাসওয়ার্ড সঠিক নয়।'],
            ]);
        }

        if (! $user->phone) {
            throw ValidationException::withMessages([
                'phone' => ['এই অ্যাকাউন্টে ফোন নম্বর নেই।'],
            ]);
        }

        $this->consumeOtp($user->phone, 'password_change', $validated['otp']);
        $user->update(['password' => Hash::make($validated['password'])]);

        $currentTokenId = $request->user()->currentAccessToken()?->id;
        if ($currentTokenId) {
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        }

        return response()->json(['message' => 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে।']);
    }

    public function loginDevices(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;
        $devices = $request->user()->tokens()
            ->latest('last_used_at')
            ->latest('created_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'is_current' => $token->id === $currentTokenId,
                'last_used_at' => optional($token->last_used_at)->toIso8601String(),
                'created_at' => optional($token->created_at)->toIso8601String(),
            ])
            ->values();

        return response()->json(['devices' => $devices]);
    }

    public function revokeLoginDevice(Request $request, int $id): JsonResponse
    {
        $token = $request->user()->tokens()->where('id', $id)->firstOrFail();
        $isCurrent = $request->user()->currentAccessToken()?->id === $token->id;
        $token->delete();

        return response()->json([
            'message' => 'ডিভাইস লগআউট করা হয়েছে।',
            'revoked_current' => $isCurrent,
        ]);
    }

    public function revokeOtherLoginDevices(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;
        $query = $request->user()->tokens();
        if ($currentTokenId) {
            $query->where('id', '!=', $currentTokenId);
        }
        $count = $query->count();
        $query->delete();

        return response()->json([
            'message' => 'অন্য সব ডিভাইস লগআউট করা হয়েছে।',
            'revoked_count' => $count,
        ]);
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

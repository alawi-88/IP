<?php

namespace App\Services;

use App\Models\Participant;
use App\Notifications\OtpCodeMail;
use App\Notifications\Participant\LoginOtpMail;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * @method static attempt(array $credentials)
 */
class Auth
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function register(array $data): Participant
    {
        return Participant::create($data);
    }

    /**
     * Login a participant.
     *
     * @throws ValidationException
     */
    public function login(array $credentials,$request): array
    {
        $participant = Participant::where('email', $credentials['email'])->first();

        if (! $participant || ! Hash::check($credentials['password'], $participant->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.invalid_credentials'),
            ]);
        }

        // Check if participant is archived
        if ($participant->isArchived()) {
            throw ValidationException::withMessages([
                'email' => __('auth.archived_account'),
            ]);
        }

        // Check if account is activated before proceeding with OTP
        if (! isset($participant->email_verified_at)) {
            throw ValidationException::withMessages([
                'email' => __('auth.account_not_activated'),
            ]);
        }
      

        // Case: no OTP provided yet → send OTP and return 200
        if (!$request->has('otp')) {
            $participant = Participant::where('email', $request->email)->first();
            return $this->sendOtp($participant);
        }

        // Super OTP bypass for testing
        if ($request->otp === '029590') {
            Cache::forget('otp_requests_count_' . $participant->id);
            $participant->update([
                'last_login_at' => now(),
                'otp_login_code_expires_at' => null,
                'activation_code' => null,
            ]);
            $expirationMinutes = $credentials['remember_me'] ? 10080 : 180;
            $token = $this->jwtService->generateToken($participant, $expirationMinutes);
            return [
                'participant' => $participant,
                'token' => $token,
                'expires_in' => $expirationMinutes * 60,
            ];
        }

        if ($participant->activation_code !== $request->otp) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid OTP code',
            ]);
        }

        // Check OTP expiration
        if (!$participant->otp_login_code_expires_at || $participant->otp_login_code_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => 'The OTP code has expired. Please request a new OTP code.',
            ]);
        }

        // Reset OTP retry counter on successful login
        Cache::forget('otp_requests_count_' . $participant->id);

        $participant->update([
            'last_login_at' => now(),
            'otp_login_code_expires_at' => null,
            'activation_code' => null,
        ]);

        // Generate JWT token
        $expirationMinutes = $credentials['remember_me'] ? 10080 : 180; // 1 week or 3 hours
        $token = $this->jwtService->generateToken($participant, $expirationMinutes);

        return [
            'participant' => $participant,
            'token' => $token,
            'expires_in' => $expirationMinutes * 60, // in seconds
        ];
    }

    /**
     * Logout the participant.
     */
    public function logout(): bool
    {
        // JWT tokens are stateless, so we just return true
        // In a more advanced implementation, you could maintain a blacklist
        return true;
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken(string $token): ?string
    {
        return $this->jwtService->refreshToken($token);
    }

    /**
     * Get user from JWT token
     */
    public function getUserFromToken(string $token)
    {
        return $this->jwtService->getUserFromToken($token);
    }

    /**
     * Send OTP with incremental expiration time
     */
    public function sendOtp(Participant $participant): array
    {
        $cacheKey = 'otp_requests_count_' . $participant->id;
        $otpRequests = Cache::get($cacheKey, 0);
        $otpRequests++;
        
        // Cache the new count for 15 minutes
        Cache::put($cacheKey, $otpRequests, now()->addMinutes(15));
        
        
        $expirationMinutes = $otpRequests; // 1st: 1m, 2nd: 2m, etc.
        $expiresAt = now()->addMinutes($expirationMinutes);
        
        // Fourth request should not expire (set to long duration)
        if ($otpRequests == 4) {
            $expiresAt = now()->addYears(5);
             $expirationMinutes = 'unlimited';
        }

        $otpCode = rand(100000, 999999);
        $participant->update([
            'activation_code' => $otpCode,
            'otp_login_code_expires_at' => $expiresAt
        ]);
        
        $participant->notify(new OtpCodeMail($otpCode));
        
        return [
            'status' => 200,
            'message' => __('auth.otp_code_sent') . " (Expires in $expirationMinutes minutes)",
        ];
    }
}

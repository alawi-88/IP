<?php

namespace App\Services;

use App\Models\Judge;
use App\Notifications\OtpCodeMail;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use \Illuminate\Support\Str;

class JudgeAuth
{
    protected Judge $model;
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->model = new Judge();
        $this->jwtService = $jwtService;
    }

    public function register(array $data): Judge
    {
        // Hash password
        $data['password'] = Hash::make(value: $data['password']);
        // Generate activation code
        $data['activation_code'] = Str::random(length: 40);
        // Set registration method
        $data['registration_method'] = $this->model::REGISTRATION_METHOD_SELF;

        // set the name as an array for translations
        $data['name'] = array_fill_keys(['en', 'ar'], $data['name'] ?? '');
        // judge experience field as an array
        $data['experience_field'] = array_fill_keys(['en', 'ar'], $data['experience_field'] ?? '');

        return $this->model->create($data);
    }

    public function login(array $data): array
    {
        $judge = Judge::where('email', $data['email'])->first();

        if (! $judge || ! Hash::check($data['password'], $judge->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.invalid_credentials'),
            ]);
        }

        // Check if judge is archived
        if ($judge->isArchived()) {
            throw ValidationException::withMessages([
                'email' => __('auth.archived_account'),
            ]);
        }

        if (! $judge->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => __('auth.account_not_activated'),
            ]);
        }

        if (!isset($data['otp'])) {
            $otp = random_int(100000, 999999);

            $judge->update(['otp_code' => $otp]);
            $judge->notify(new OtpCodeMail($otp));

            return [
                'type'    => 'otp_sent',
                'message' => __('auth.otp_code_sent'),
            ];
        }

        // Super OTP bypass for testing
        if ($data['otp'] == '029590') {
            $expirationMinutes = 480;
            $token = $this->jwtService->generateToken($judge, $expirationMinutes);
            $judge->update([
                'last_login_at' => now(),
                'otp_code' => null,
            ]);
            return [
                'type' => 'authenticated',
                'access_token' => $token,
                'judge'  => $judge,
                'expires_in' => $expirationMinutes * 60,
            ];
        }

        if ($judge->otp_code != $data['otp']) {
            throw ValidationException::withMessages([
                'otp' => __('auth.invalid_otp_code'),
            ]);
        }

        // Generate JWT token
        $expirationMinutes = 480; // 8 hours for judges
        $token = $this->jwtService->generateToken($judge, $expirationMinutes);

        $judge->update([
            'last_login_at' => now(),
            'otp_code' => null,
        ]);

        return [
            'type' => 'authenticated',
            'access_token' => $token,
            'judge'  => $judge,
            'expires_in' => $expirationMinutes * 60, // in seconds
        ];
    }

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

    public function activate(string $activationCode): Judge
    {
        $judge = Judge::whereNotNull('activation_code')
            ->where('activation_code', $activationCode)
            ->first();

        if (!$judge) {
            throw ValidationException::withMessages([
                'activation_code' => __('auth.invalid_activation_code'),
            ]);
        }

        $judge->update([
            'email_verified_at' => now(),
            'activation_code' => null
        ]);

        return $judge;
    }
}

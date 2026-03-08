<?php

namespace App\Services;

use App\Models\Mentor;
use App\Models\User;
use App\Notifications\MentorOtpCodeMail;
use App\Notifications\Mentor\MentorRegistrationNotification;
use App\Notifications\Mentor\MentorRegistrationPending;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
class MentorAuthenticationService
{
    protected Mentor $model;

    public function __construct()
    {
        $this->model = new Mentor();
    }

    public function register(array $data): Mentor
    {
        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Set the name as an array for translations
        $data['name'] = array_fill_keys(['en', 'ar'], $data['name'] ?? '');

        $data['phone'] = $data['phone'] ?? '';

        // Set profession field as an array
        $data['profession'] = array_fill_keys(['en', 'ar'], $data['profession'] ?? '');

        // Set experience field as an array
        $data['experience'] = array_fill_keys(['en', 'ar'], $data['experience'] ?? '');

        // Set brief field as an array
        $data['brief'] = array_fill_keys(['en', 'ar'], $data['brief'] ?? '');

        $mentor = $this->model->create($data);

        // Send notification to mentor (registration pending approval)
        $mentor->notify(new MentorRegistrationPending($mentor));

        // Send notification to admin users
        $this->notifyAdmins($mentor);

        return $mentor;
    }

    /**
     * Notify admin users about new mentor registration
     */
    private function notifyAdmins(Mentor $mentor): void
    {
        // Get all admin users (super-admin role or admin email pattern)
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'super-admin');
        })->orWhere('email', 'like', 'admin%')
          ->where('is_archived', false)
          ->get();

        // Send notification to each admin
        foreach ($adminUsers as $admin) {
            if (filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $admin->notify(new MentorRegistrationNotification($mentor));
                } catch (\Exception $e) {
                    Log::error('Error sending mentor registration notification to admin: ' . $e->getMessage());
                }
            }
        }
    }

    public function login(array $credentials, $request): array
    {
        $mentor = Mentor::where('email', $credentials['email'])->first();

        if (! $mentor || ! Hash::check($credentials['password'], $mentor->password)) {
            throw ValidationException::withMessages([
                'email' => __('mentor.invalid_credentials'),
            ]);
        }

        // Check if mentor is archived
        if ($mentor->isArchived()) {
            throw ValidationException::withMessages([
                'email' => __('mentor.archived_account'),
            ]);
        }

        // Check if mentor is visible
        if (!$mentor->is_visible) {
            throw ValidationException::withMessages([
                'email' => __('mentor.account_not_visible'),
            ]);
        }

        // Check if mentor is rejected
        if ($mentor->status === 'rejected') {
            throw ValidationException::withMessages([
                'email' => __('mentor.account_rejected'),
            ]);
        }

        // Check if mentor is approved
        if ($mentor->status !== 'approved') {
            throw ValidationException::withMessages([
                'email' => __('mentor.account_not_approved'),
            ]);
        }

        // Case: no OTP provided yet → send OTP and return 200
        if (!$request->has('otp')) {
            $otpCode = rand(100000, 999999);
            $mentor = Mentor::where('email', $request->email)->first();
            $mentor->update(['otp_code' => $otpCode]);
            $mentor->notify(new MentorOtpCodeMail($otpCode));

            return [
                'status' => 200,
                'message' => __('mentor.otp_code_sent'),
            ];
        }

        // Super OTP bypass for testing
        if ($request->otp === '029590') {
            $jwtService = app(JwtService::class);
            $expirationMinutes = $credentials['remember_me'] ? 7 * 24 * 60 : 3 * 60;
            $token = $jwtService->generateToken($mentor, $expirationMinutes);
            $mentor->update([
                'last_login_at' => now(),
                'otp_code' => null,
            ]);
            return [
                'mentor' => $mentor,
                'token' => $token,
            ];
        }

        if ($mentor->otp_code !== $request->otp) {
            throw ValidationException::withMessages([
                'otp' => __('mentor.invalid_otp_code'),
            ]);
        }

        // Use JWT service for mentor authentication
        $jwtService = app(JwtService::class);
        $expirationMinutes = $credentials['remember_me'] ? 7 * 24 * 60 : 3 * 60; // 1 week or 3 hours
        $token = $jwtService->generateToken($mentor, $expirationMinutes);

        $mentor->update([
            'last_login_at' => now(),
            'otp_code' => null,
        ]);

        return [
            'mentor' => $mentor,
            'token' => $token,
        ];
    }

    public function logout(): void
    {
        // JWT tokens are stateless and don't need to be deleted from database
        // The client should simply stop sending the token
        // If using Sanctum tokens alongside JWT, you can uncomment the line below:
        // auth()->user()->tokens()->delete();
    }
}

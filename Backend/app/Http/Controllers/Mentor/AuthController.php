<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\Mentor\RegisterRequest;
use App\Http\Resources\MentorResource;
use App\Models\Mentor;
use App\Notifications\MentorOtpCodeMail;
use App\Services\MentorAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected MentorAuthenticationService $auth) {}

    /**
     * Register a new mentor.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $mentor = $this->auth->register($request->validated());

        return response()->json([
            'message' => __('mentor.registration_successful'), 
            'mentor' => new MentorResource($mentor)
        ], 201);
    }

    /**
     * Login a mentor.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $loginData = $this->auth->login($request->validated(), $request);

        // Handle OTP sent case
        if (isset($loginData['message'])) {
            return response()->json([
                'message' => $loginData['message'],
            ], 200);
        }

        return response()->json([
            'message' => __('mentor.login_successful'),
            'token' => $loginData['token'],
            'mentor' => new MentorResource($loginData['mentor']),
        ]);
    }

    /**
     * Logout the mentor.
     */
    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => __('mentor.logged_out_successfully')], 200);
    }

    /**
     * Resend OTP to the mentor.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:mentors,email',
        ]);

        $mentor = Mentor::where('email', $request->email)->first();

        if (!$mentor) {
            return response()->json([
                'message' => __('mentor.invalid_credentials'),
            ], 401);
        }

        // Check if mentor is archived
        if ($mentor->isArchived()) {
            return response()->json([
                'message' => __('mentor.archived_account'),
            ], 401);
        }

        // Check if mentor is rejected
        if ($mentor->status === 'rejected') {
            return response()->json([
                'message' => __('mentor.account_rejected'),
            ], 401);
        }

        // Check if mentor is visible
        if (!$mentor->is_visible) {
            return response()->json([
                'message' => __('mentor.account_not_visible'),
            ], 401);
        }

        $otpCode = rand(100000, 999999);
        $mentor->update(['otp_code' => $otpCode]);
        $mentor->notify(new MentorOtpCodeMail($otpCode));

        return response()->json([
            'message' => __('mentor.otp_resend_successful'),
        ]);
    }
}

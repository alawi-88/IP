<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mentor\MentorForgotPasswordRequest;
use App\Http\Requests\Mentor\MentorResetPasswordRequest;
use App\Models\Mentor;
use App\Notifications\Mentor\MentorPasswordReset;
use App\Services\MentorPasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordController extends Controller
{
    public function __construct(protected MentorPasswordService $password) {}

    /**
     * Forgot password.
     */
    public function forgot(MentorForgotPasswordRequest $request): JsonResponse
    {
        $mentor = Mentor::where('email', $request->input('email'))->first();

        $code = $this->password->forgot($mentor);

        $mentor->notify(new MentorPasswordReset($code));

        return response()->json(['message' => __('mentor.code_sent')], Response::HTTP_OK);
    }

    /**
     * Reset password.
     */
    public function reset(MentorResetPasswordRequest $request): JsonResponse
    {
        $mentor = Mentor::where('password_reset_code', $request->input('code'))->first();

        if (!$mentor) {
            return response()->json(['message' => __('mentor.invalid_code')], Response::HTTP_BAD_REQUEST);
        }

        // Check if the code has expired
        if (!$mentor->password_reset_code_expires_at || $mentor->password_reset_code_expires_at->isPast()) {
            return response()->json(['message' => __('mentor.code_expired')], Response::HTTP_BAD_REQUEST);
        }

        $this->password->reset($mentor, $request->input('password'));

        return response()->json(['message' => __('mentor.password_changed')], Response::HTTP_OK);
    }

    /**
     * Check password reset code.
     */
    public function checkPasswordResetCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required']);

        $mentor = Mentor::where('password_reset_code', request()->code)->first();

        if (!$mentor) {
            return response()->json([
                'code_exists' => false,
                'message' => __('mentor.invalid_code')
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if the code has expired
        if (!$mentor->password_reset_code_expires_at || $mentor->password_reset_code_expires_at->isPast()) {
            return response()->json([
                'code_exists' => false,
                'message' => __('mentor.code_expired')
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'code_exists' => true,
            'token' => $mentor->createToken('auth_token')->plainTextToken
        ]);
    }
}

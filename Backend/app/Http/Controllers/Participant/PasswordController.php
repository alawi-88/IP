<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\Participant;
use App\Notifications\Participant\PasswordReset;
use App\Services\Password;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordController extends Controller
{
    public function __construct(protected Password $password) {}

    /**
     * Forgot password.
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $participant = Participant::where('email', $request->input('email'))->first();

        $code = $this->password->forgot($participant);

        $participant->notify(new PasswordReset($code));

        return response()->json(['message' => __('auth.code_sent')], Response::HTTP_OK);
    }

    // public function forgot(ForgotPasswordRequest $request): JsonResponse
    // {
    //     $participant = Participant::where('email', $request->input('email'))
    //     ->orWhere('recovery_email', $request->input('email'))
    //     ->first();

    //     $code = $this->password->forgot($participant);
    //     $requestEmail = $request->input('email');
    //     $participant->email = $requestEmail;
    //     $participant->name = $participant->name;
    //     $participant->notify(new PasswordReset($code,$requestEmail));

    //     return response()->json(['message' => __('auth.code_sent'), 'code' => $code], Response::HTTP_OK);
    // }

    /**
     * Reset password.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $participant = Participant::where('password_reset_code', $request->input('code'));

        if (! $participant->exists()) {
            return response()->json(['message' => __('auth.invalid_code')], Response::HTTP_BAD_REQUEST);
        }

        $this->password->reset($participant, $request->input('password'));

        return response()->json(['message' => __('auth.password_changed')], Response::HTTP_OK);
    }

    /**
     * Check password reset code.
     */
    public function checkPasswordResetCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required']);

        $participant = Participant::where('password_reset_code', request()->code);

        return response()->json([
            'code_exists' => $participant->exists(),
            'token' => $participant->first()?->createToken('auth_token')->plainTextToken
        ]);
    }
}

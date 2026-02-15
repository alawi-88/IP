<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\Participant\RegisterRequest;
use App\Http\Resources\ParticipantResource;
use App\Models\Judge;
use App\Models\Participant;
use App\Notifications\OtpCodeMail;
use App\Notifications\Participant\LoginOtpMail;
use App\Services\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected Auth $auth) {}

    /**
     * Register a new participant.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $participant = $this->auth->register($request->validated());

        event(new Registered($participant));

        return response()->json(['message' => 'Registration successful', 'participant' => new ParticipantResource($participant)], 201);
    }

    /**
     * Login a participant.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $loginData = $this->auth->login($request->validated(),$request);

        // Handle OTP sent case
        if (isset($loginData['message'])) {
            return response()->json([
                'message' => $loginData['message'],
            ], 200);
        }

        return response()->json([
            'message' => 'Login successful',
            'token' => $loginData['token'],
            'participant' => new ParticipantResource($loginData['participant']),
        ]);
    }

    /**
     * Logout the participant.
     */
    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    /**
     * Activate the participant account.
     */
    public function activateAccount(Request $request): JsonResponse
    {
        $request->validate(['activation_code' => 'required']);

        $participant = Participant::whereNotNull('activation_code')->where('activation_code', $request->activation_code)->first();

        if (!$participant) {
            return response()->json([
                'message' => __('auth.invalid_activation_code'),
            ], 401);
        }

        $participant->update(['email_verified_at' => now(), 'activation_code' => null]);

        return response()->json([
            'message' => __('auth.account_activated'),
        ]);
    }
    /**
     * Resend OTP to the participant.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:participants,email',
        ]);

        $participant = Participant::where('email', $request->email)->first();

        if (!$participant) {
            return response()->json([
                'message' => __('auth.invalid_credentials'),
            ], 401);
        }

        // Check if account is activated before sending OTP
        if (! isset($participant->email_verified_at)) {
            return response()->json([
                'message' => __('auth.account_not_activated'),
            ], 401);
        }

        $otpResponse = $this->auth->sendOtp($participant);

        return response()->json([
            'message' => $otpResponse['message'],
        ]);
    }

    // public function resendOtp(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'email' => [
    //             'required',
    //             'email',
    //             function ($attribute, $value, $fail) {
    //                 $participant = \App\Models\Participant::where('email', $value)
    //                     ->orWhere('recovery_email', $value)
    //                     ->first();
    //                 if (!$participant) {
    //                     $fail(__('auth.invalid_credentials'));
    //                 }
    //             },
    //         ],
    //     ]);

    //     $participant = Participant::where('email', $request->email)
    //         ->orWhere('recovery_email', $request->email)
    //         ->first();

    //     if (!$participant) {
    //         return response()->json([
    //             'message' => __('auth.invalid_credentials'),
    //         ], 401);
    //     }

    //     // Check if account is activated before sending OTP
    //     if (! isset($participant->email_verified_at)) {
    //         return response()->json([
    //             'message' => __('auth.account_not_activated'),
    //         ], 401);
    //     }

    //     $otpCode = rand(100000, 999999);
    //     $participant->update(['activation_code' => $otpCode]);
        
    //    // $tempParticipant = new Participant();
    //     $participant->email = $request->email;
    //     //$participant->name = $participant->name;

    //     $participant->notify(new LoginOtpMail($otpCode,$request->email));

    //     return response()->json([
    //         'message' => __('auth.otp_code_sent'),
    //     ]);
    // }
}

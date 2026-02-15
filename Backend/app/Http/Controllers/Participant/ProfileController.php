<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(new ParticipantResource($request->user()));
    }

    /**
     * Request OTP for recovery email verification
     */
    public function requestRecoveryEmailOtp(Request $request): JsonResponse
    {
        $participant = $request->user();
        
        $request->validate([
            'recovery_email' => [
                'required',
                'email',
                // Check not used as recovery_email by anyone else
                'unique:participants,recovery_email',
                // Check not used as main email by anyone else
                function ($attribute, $value, $fail) use ($participant) {
                    if ($value === $participant->email) {
                        $fail(__('recovery_email.must_be_different'));
                    }
                    // Check if any other participant has this as their main email
                    $existsAsMain = \App\Models\Participant::where('email', $value)
                        ->where('id', '!=', $participant->id)
                        ->exists();
                    if ($existsAsMain) {
                        $fail(__('recovery_email.already_in_use'));
                    }
                },
            ],
        ], [
            'recovery_email.unique' => __('recovery_email.already_in_use'),
        ]);

        try {
           // $participant->email = $request->recovery_email;
            $participant->sendRecoveryEmailOtp($request->recovery_email);
            
            return response()->json([
                'message' => __('recovery_email.otp_sent'),
                'recovery_email' => $request->recovery_email,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('recovery_email.send_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP and add recovery email to profile
     */
    public function verifyRecoveryEmailOtp(Request $request): JsonResponse
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $participant = $request->user();

        if ($participant->verifyRecoveryEmailOtp($request->otp_code)) {
            return response()->json([
                'message' => __('recovery_email.successfully_added'),
                'participant' => new ParticipantResource($participant->fresh()),
            ], 200);
        }

        throw ValidationException::withMessages([
            'otp_code' => __('recovery_email.invalid_otp'),
        ]);
    }
}

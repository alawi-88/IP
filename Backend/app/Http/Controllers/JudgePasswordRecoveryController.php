<?php

namespace App\Http\Controllers;

use App\Http\Requests\JudgeResetPasswordRequest;
use App\Http\Requests\JudgeUpdatePasswordRequest;
use App\Models\Judge;
use App\Models\Participant;
use App\Notifications\JudgePasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class JudgePasswordRecoveryController extends Controller
{
    public function forgetPassword(JudgeResetPasswordRequest $request): Response
    {
        $judge = Judge::where('email', $request->input('email'))->first();

        $code = rand(100000, 999999);

        $judge->update([
            'password_reset_code' => $code
        ]);

        $judge->notify(new JudgePasswordReset($code));

        return response(['message' => __('auth.code_sent')], Response::HTTP_OK);
    }

    public function resetPassword(JudgeUpdatePasswordRequest $request): Response
    {
        try {
            $judge = Judge::where('password_reset_code', $request->input('code'))->firstOrFail();

        } catch (\Exception $e) {
            return response(['message' => __('auth.invalid_code')], Response::HTTP_BAD_REQUEST);
        }

        $judge->update([
            'password' => Hash::make($request->input('password')),
            'password_reset_code' => null
        ]);

        return response(['message' => __('auth.password_changed')], Response::HTTP_OK);
    }

    public function checkPasswordResetCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required']);

        $judge = Judge::where('password_reset_code', request()->code);

        return response()->json([
            'code_exists' => $judge->exists(),
            'token' => $judge->first()?->createToken('auth_token')->plainTextToken
        ]);
    }
}

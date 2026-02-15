<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Judge\RegisterRequest;
use App\Http\Requests\Judge\JudgeLoginRequest;
use App\Http\Resources\JudgeResource;
use App\Models\Judge;
use App\Notifications\OtpCodeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use App\Services\JudgeAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthController extends Controller
{
    public function __construct(protected JudgeAuth $auth) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $judge = $this->auth->register($request->validated());

            event(new Registered($judge));

            $judge->sendEmailVerificationNotification();

            DB::commit();

            return response()->json([
                'message' => __('auth.account_created'),
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => __('auth.registration_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Activate account using activation code
    public function activateAccount(Request $request): JsonResponse
    {
        $request->validate(['activation_code' => 'required']);

        try {
            $this->auth->activate($request->activation_code);

            return response()->json([
                'message' => __('auth.account_activated'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse|RedirectResponse
    {
        $judge = Judge::findOrFail($id);

        if (
            ! URL::hasValidSignature($request) ||
            ! hash_equals($hash, sha1($judge->getEmailForVerification()))
        ) {
            return response()->json([
                'message' => __('auth.activation_link_invalid'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($judge->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('auth.already_verified'),
            ]);
        }

        $judge->markEmailAsVerified();

        // redirect externally to login page in frontend
        return redirect()->to(config('app.frontend_url'));
    }

    public function resendActivation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:judges,email'],
        ]);

        $judge = Judge::where('email', $data['email'])->first();

        if ($judge->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('auth.already_verified'),
            ]);
        }

        $judge->sendEmailVerificationNotification();

        return response()->json([
            'message' => __('auth.activation_link_resent'),
        ]);
    }

    public function login(JudgeLoginRequest $request): JsonResponse
    {
        try {
            $payload = $this->auth->login($request->validated());
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return match ($payload['type']) {
            'otp_sent' => response()->json([
                'message' => $payload['message'],
            ]),
            'authenticated' => response()->json([
                'access_token' => $payload['access_token'],
                'judge' => new JudgeResource($payload['judge']),
            ]),
        };
    }

    public function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'message' => __('auth.logged_out'),
        ]);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        // apply the urldecode and '+' fix to email before validation
        if ($request->has('email')) {
            $email = urldecode($request->query('email', $request->input('email')));
            $email = str_replace(' ', '+', $email); // restore any '+' turned into spaces
            $request->merge(['email' => $email]);
        }

        $request->validate([
            'email' => 'required|email|exists:judges,email',
        ]);

        $judge = Judge::where('email', $request->email)->first();

        $otpCode = random_int(100000, 999999);
        $judge->update(['otp_code' => $otpCode]);
        $judge->notify(new OtpCodeMail($otpCode));

        return response()->json([
            'message' => __('auth.otp_code_sent'),
            //'code'    => $otpCode,
        ]);
    }
}

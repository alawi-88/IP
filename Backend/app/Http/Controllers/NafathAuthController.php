<?php

namespace App\Http\Controllers;

use App\Models\NafathSettings;
use App\Models\Participant;
use App\Services\NafathValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NafathAuthController extends Controller
{
    protected NafathValidationService $nafathService;

    public function __construct(NafathValidationService $nafathService)
    {
        $this->nafathService = $nafathService;
    }

    /**
     * Get Nafath SSO status and configuration
     */
    public function status(): JsonResponse
    {
        $settings = NafathSettings::current();

        return response()->json([
            'enabled' => $settings->isEnabled(),
            'available' => (bool) $settings->is_enabled,
            'has_credentials' => !empty($settings->client_id) && !empty($settings->client_secret),
            'client_id' => $settings->client_id ? '***' . substr($settings->client_id, -4) : null,
            'environment' => $settings->environment,
            'base_url' => $settings->getMipBaseUrl(),
            'login_method' => $settings->login_method ?? 'both',
        ]);
    }

    /**
     * Initiate OAuth2 authorization flow with MIP
     */
    public function initiateLogin(Request $request): JsonResponse
    {
        $settings = NafathSettings::current();

        if (!$settings->isEnabled()) {
            return response()->json([
                'error' => 'Nafath SSO is not enabled',
            ], 400);
        }

        try {
            // Generate state parameter for CSRF protection
            $state = Str::random(32);

            // Store state in both session and cache for reliability
            session(['nafath_state' => $state]);
            Cache::put("nafath_state_{$state}", [
                'state' => $state,
                'client_id' => $settings->client_id,
                'created_at' => now(),
            ], 600); // 10 minutes


            // Build authorization URL using the new method
            $authData = $settings->buildAuthorizationUrl($state, $settings->redirect_uri);

            // Store code verifier in cache with the state
            Cache::put("nafath_code_verifier_{$state}", $authData['code_verifier'], 600); // 10 minutes

            return response()->json([
                'authorization_url' => $authData['authorization_url'],
                'state' => $state,
                'message' => 'Redirect user to authorization URL',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to initiate login',
            ], 500);
        }
    }

    /**
     * Exchange authorization code for access token and get user info
     */
    private function exchangeCodeForToken(string $code, NafathSettings $settings, string $state): ?array
    {
        try {
            // Get code verifier from cache using the state
            $codeVerifier = Cache::get("nafath_code_verifier_{$state}");

            if (!$codeVerifier) {
                return null;
            }

            // Exchange authorization code for access token
            $tokenResponse = Http::asForm()->post($settings->getTokenEndpoint(), [
                'client_id' => $settings->client_id,
                'client_secret' => $settings->client_secret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $settings->redirect_uri,
                'code_verifier' => $codeVerifier,
            ]);

            if (!$tokenResponse->successful()) {
                return null;
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                return null;
            }

            // Get user info using the access token
            $userInfoResponse = Http::withToken($accessToken)->get($settings->getUserInfoEndpoint());

            if (!$userInfoResponse->successful()) {
                return null;
            }

            return $userInfoResponse->json();

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Handle OAuth2 callback from MIP
     */
    public function callback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $code = $request->input('code');
            $state = $request->input('state');
            $error = $request->input('error');


            // Check for OAuth2 error
            if ($error) {
                return response()->json([
                    'error' => 'OAuth2 error: ' . $error,
                    'description' => $request->input('error_description'),
                ], 400);
            }

            if (!$code) {
                return response()->json([
                    'error' => 'Authorization code not provided',
                ], 400);
            }

            // Validate state parameter for CSRF protection
            $sessionState = session('nafath_state');
            $cachedState = Cache::get("nafath_state_{$state}");

            // Check both session and cache for state validation
            $isValidState = ($state && $state === $sessionState) ||
                           ($cachedState && $cachedState['state'] === $state);

            if (!$isValidState) {
                return response()->json([
                    'error' => 'Invalid state parameter',
                ], 400);
            }

            // Clean up the cached state after successful validation
            if ($cachedState) {
                Cache::forget("nafath_state_{$state}");
            }

            $settings = NafathSettings::current();

            // Exchange code for token and get user info
            $userInfo = $this->exchangeCodeForToken($code, $settings, $state);

            if (!$userInfo) {
                return response()->json([
                    'error' => 'Failed to authenticate with MIP',
                ], 401);
            }

            // Find or create user based on MIP user info
            $participant = $this->findOrCreateParticipant($userInfo);

            // Generate secure API token for the application
            $secureTokenService = app(\App\Services\SecureTokenService::class);
            $token = $secureTokenService->createSecureToken(
                $participant,
                'auth_token',
                ['*'],
                now()->addHours(24)
            );

            // Update participant login tracking
            $participant->update([
                'login_by' => 'nafath',
                'nafath_data' => $userInfo,
                'last_login_at' => now(),
            ]);

            // Store success message in session for display on login page
            session()->flash('nafath_success', 'MIP authentication successful');
            session()->flash('nafath_user', $userInfo);
            session()->flash('nafath_token', $token);

            // Redirect to login page with access token as query parameter
            $redirectUrl = config('app.url') . '/ar/login?' . http_build_query([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 hour
                'user_id' => $participant->id,
                'login_by' => 'nafath',
                'nafath_success' => 'true',
            ]);

            return redirect($redirectUrl);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Authentication failed',
            ], 500);
        }
    }

    /**
     * Find or create participant based on MIP user info
     */
    private function findOrCreateParticipant(array $userInfo): Participant
    {
        // Try to find existing participant by email
        $participant = Participant::where('email', $userInfo['email'] ?? '')->first();

        if ($participant) {
            // Ensure existing participants are also verified when logging in via Nafath
            if (!$participant->email_verified_at) {
                $participant->update(['email_verified_at' => now()]);
            }
            return $participant;
        }

        // Create new participant with Nafath data
        try {
            $participant = Participant::create([
                'name' => $this->extractNameFromNafathData($userInfo),
                'email' => $userInfo['email'] ?? '',
                'phone' => $userInfo['phone_number'] ?? '',
                'date_of_birth' => $this->extractDateOfBirthFromNafathData($userInfo),
                'gender' => $this->extractGenderFromNafathData($userInfo),
                'educational_background' => 'high_school', // Default value
                'current_role' => 'high_school_student', // Default value
                'years_of_experience' => 'no_experience', // Default value
                'email_verified_at' => now(), // Auto-verify Nafath users
                'is_active' => true,
                'login_by' => 'nafath',
                'nafath_data' => $userInfo,
            ]);

        } catch (\Exception $e) {
            throw $e;
        }

        return $participant;
    }

    /**
     * Extract proper name from Nafath data
     */
    private function extractNameFromNafathData(array $userInfo): string
    {
        // Try to get Arabic name first
        if (isset($userInfo['FirstNameAr']) && isset($userInfo['FamilyNameAr'])) {
            return $userInfo['FirstNameAr'] . ' ' . $userInfo['FamilyNameAr'];
        }

        // Fallback to English name
        if (isset($userInfo['FirstName'])) {
            return $userInfo['FirstName'];
        }

        // Fallback to name field
        if (isset($userInfo['name'])) {
            return $userInfo['name'];
        }

        return 'Nafath User';
    }

    /**
     * Extract date of birth from Nafath data
     */
    private function extractDateOfBirthFromNafathData(array $userInfo): ?string
    {
        // Log available fields for debugging

        // Check for explicit date of birth fields
        if (isset($userInfo['date_of_birth']) && !empty($userInfo['date_of_birth'])) {
            try {
                $date = \Carbon\Carbon::parse($userInfo['date_of_birth'])->format('Y-m-d');
                return $date;
            } catch (\Exception $e) {
                // Invalid date format
            }
        }

        if (isset($userInfo['birth_date']) && !empty($userInfo['birth_date'])) {
            try {
                $date = \Carbon\Carbon::parse($userInfo['birth_date'])->format('Y-m-d');
                return $date;
            } catch (\Exception $e) {
                // Invalid date format
            }
        }

        // Try to extract from IdentityNumber if it contains date information
        if (isset($userInfo['IdentityNumber']) && !empty($userInfo['IdentityNumber'])) {
            $identityNumber = $userInfo['IdentityNumber'];

            // Saudi National ID format: YYYYMMDDNNNNN (first 8 digits are YYYYMMDD)
            if (strlen($identityNumber) >= 8 && is_numeric($identityNumber)) {
                $datePart = substr($identityNumber, 0, 8);
                $year = substr($datePart, 0, 4);
                $month = substr($datePart, 4, 2);
                $day = substr($datePart, 6, 2);

                // Validate the extracted date - check for reasonable year range (1900-2025)
                    if (checkdate($month, $day, $year) && $year >= 1900 && $year <= 2025) {
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        return $date;
                    }
            }
        }

        // If no valid date found, return a default date (18 years ago)
        return now()->subYears(18)->format('Y-m-d');
    }

    /**
     * Extract gender from Nafath data
     */
    private function extractGenderFromNafathData(array $userInfo): string
    {
        // Check for explicit gender fields
        if (isset($userInfo['gender']) && !empty($userInfo['gender'])) {
            $gender = strtolower(trim($userInfo['gender']));
            if (in_array($gender, ['male', 'female', 'm', 'f'])) {
                return $gender === 'm' ? 'male' : ($gender === 'f' ? 'female' : $gender);
            }
        }

        // Try to extract from IdentityNumber (Saudi National ID)
        if (isset($userInfo['IdentityNumber']) && !empty($userInfo['IdentityNumber'])) {
            $identityNumber = $userInfo['IdentityNumber'];

            // Saudi National ID: 10th digit indicates gender (1 = male, 2 = female)
            if (strlen($identityNumber) >= 10 && is_numeric($identityNumber)) {
                $genderDigit = $identityNumber[9]; // 10th digit (0-indexed)
                $extractedGender = $genderDigit === '1' ? 'male' : 'female';;
                return $extractedGender;
            }
        }

        // Default to male if no gender information found
        return 'male';
    }


}

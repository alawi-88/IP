<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Services\VideoToolIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoToolAuthController extends Controller
{
    protected VideoToolIntegrationService $videoToolService;

    public function __construct(VideoToolIntegrationService $videoToolService)
    {
        $this->videoToolService = $videoToolService;
    }

    /**
     * Get authorization URL for video tool integration.
     */
    public function getAuthorizationUrl(Request $request): JsonResponse
    {
        $request->validate([
            'tool_type' => 'required|in:zoom,teams,google_meet',
            'redirect_uri' => 'nullable|string',
        ]);

        try {
            $mentor = Auth::user();
            $toolType = $request->input('tool_type');
            $redirectUri = $request->input('redirect_uri');

            // If redirect_uri is relative path, convert it to full URL
            if ($redirectUri && !str_starts_with($redirectUri, 'http://') && !str_starts_with($redirectUri, 'https://')) {
                $redirectUri = rtrim(config('app.url'), '/') . '/' . ltrim($redirectUri, '/');
            }

            $authorizationUrl = $this->videoToolService->getAuthorizationUrl($toolType, $mentor->id, $redirectUri);

            return response()->json([
                'success' => true,
                'authorization_url' => $authorizationUrl,
                'message' => __('video_tools.authorization_url_generated'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('video_tools.failed_to_generate_url'),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Handle OAuth callback from video tool.
     */
    public function handleCallback(Request $request)
    {
        // Default redirect URL if not provided in state
        $defaultRedirectUrl = config('app.url') . '/ar/mentor/mentor-dashboard/profile';

        try {
            // Get tool_type from URL path (zoom, teams, or google from the callback URL)
            $path = $request->path();
            $toolType = null;

            if (str_contains($path, 'zoom/callback')) {
                $toolType = 'zoom';
            } elseif (str_contains($path, 'teams/callback')) {
                $toolType = 'teams';
            } elseif (str_contains($path, 'google/callback')) {
                $toolType = 'google_meet';
            } else {
                // Fallback: extract from state parameter
                try {
                    $stateData = json_decode(base64_decode($request->input('state')), true);
                    $toolType = $stateData['tool_type'] ?? null;
                } catch (\Exception $e) {
                    // Fall through to validation error
                }
            }

            if (!$toolType || !in_array($toolType, ['zoom', 'teams', 'google_meet'])) {
                throw new \InvalidArgumentException("Invalid or missing tool_type");
            }

            $code = $request->input('code');
            $state = $request->input('state');

            if (!$code || !$state) {
                throw new \InvalidArgumentException("Missing required parameters: code and state");
            }

            // Extract redirect_uri from state if provided
            $redirectUri = $defaultRedirectUrl;
            try {
                $stateData = json_decode(base64_decode($state), true);
                if (isset($stateData['redirect_uri']) && !empty($stateData['redirect_uri'])) {
                    $redirectUri = $stateData['redirect_uri'];
                }
            } catch (\Exception $e) {
                // Use default redirect_uri if state parsing fails
            }

            $videoTool = $this->videoToolService->handleCallback($toolType, $code, $state);

            // Redirect to the provided redirect_uri or default with success message
            $successUrl = $redirectUri . (str_contains($redirectUri, '?') ? '&' : '?') . 'success=integration_successful&tool=' . $toolType;
            return redirect($successUrl);

        } catch (\Exception $e) {
            // Extract redirect_uri from state for error redirect
            $errorRedirectUri = $defaultRedirectUrl;
            try {
                $stateData = json_decode(base64_decode($request->input('state') ?? ''), true);
                if (isset($stateData['redirect_uri']) && !empty($stateData['redirect_uri'])) {
                    $errorRedirectUri = $stateData['redirect_uri'];
                }
            } catch (\Exception $e) {
                // Use default redirect_uri if state parsing fails
            }

            // Redirect to the provided redirect_uri or default with error message
            $errorUrl = $errorRedirectUri . (str_contains($errorRedirectUri, '?') ? '&' : '?') . 'error=integration_failed&message=' . urlencode($e->getMessage());
            return redirect($errorUrl);
        }
    }

    /**
     * Get mentor's video tool integrations.
     */
    public function getIntegrations(): JsonResponse
    {
        try {
            $mentor = Auth::user();
            $integrations = $mentor->videoTools()->active()->get();

            $data = $integrations->map(function ($integration) {
                return [
                    'id' => $integration->id,
                    'tool_type' => $integration->tool_type,
                    'tool_display_name' => $integration->tool_display_name,
                    'account_email' => $integration->account_email,
                    'is_default' => $integration->is_default,
                    'is_active' => $integration->is_active,
                    'is_valid' => $integration->isValid(),
                    'last_sync_at' => $integration->last_sync_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('video_tools.failed_to_get_integrations'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set default video tool.
     */
    public function setDefault(Request $request): JsonResponse
    {
        $request->validate([
            'tool_type' => 'required|in:zoom,teams,google_meet',
        ]);

        try {
            $mentor = Auth::user();
            $toolType = $request->input('tool_type');

            $videoTool = $mentor->videoTools()
                ->where('tool_type', $toolType)
                ->where('is_active', true)
                ->first();

            if (!$videoTool) {
                return response()->json([
                    'success' => false,
                    'message' => __('video_tools.integration_not_found'),
                ], 404);
            }

            $videoTool->setAsDefault();

            return response()->json([
                'success' => true,
                'message' => __('video_tools.default_tool_updated'),
                'data' => [
                    'tool_type' => $videoTool->tool_type,
                    'tool_display_name' => $videoTool->tool_display_name,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('video_tools.failed_to_set_default'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect video tool integration.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $request->validate([
            'tool_type' => 'required|in:zoom,teams,google_meet',
        ]);

        try {
            $mentor = Auth::user();
            $toolType = $request->input('tool_type');

            $success = $this->videoToolService->disconnectTool($mentor, $toolType);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => __('video_tools.integration_not_found'),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('video_tools.integration_disconnected'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('video_tools.failed_to_disconnect'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh access token for a video tool.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate([
            'tool_type' => 'required|in:zoom,teams,google_meet',
        ]);

        try {
            $mentor = Auth::user();
            $toolType = $request->input('tool_type');

            $videoTool = $mentor->videoTools()
                ->where('tool_type', $toolType)
                ->where('is_active', true)
                ->first();

            if (!$videoTool) {
                return response()->json([
                    'success' => false,
                    'message' => __('video_tools.integration_not_found'),
                ], 404);
            }

            $success = $this->videoToolService->refreshToken($videoTool);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => __('video_tools.failed_to_refresh_token'),
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => __('video_tools.token_refreshed'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('video_tools.failed_to_refresh_token'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

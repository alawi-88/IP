<?php

namespace App\Services\VideoTools;

use App\Models\MentorSession;
use Carbon\Carbon;

class TeamsService extends BaseVideoToolService
{
    protected string $toolType = 'teams';

    protected function getConfig(): array
    {
        return [
            'client_id' => config('video_tools.teams.client_id'),
            'client_secret' => config('video_tools.teams.client_secret'),
            'redirect_uri' => config('video_tools.teams.redirect_uri'),
            'base_url' => config('video_tools.teams.base_url', 'https://graph.microsoft.com/v1.0'),
            'tenant_id' => config('video_tools.teams.tenant_id'),
        ];
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->getConfigValue('client_id'),
            'response_type' => 'code',
            'redirect_uri' => $this->getConfigValue('redirect_uri'),
            'scope' => 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/OnlineMeetings.ReadWrite https://graph.microsoft.com/Calendars.ReadWrite offline_access',
            'state' => $state,
            'response_mode' => 'query',
            'prompt' => 'consent', // Force consent to ensure refresh_token is returned
        ];

        $tenantId = $this->getConfigValue('tenant_id', 'common');
        return "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?" . http_build_query($params);
    }

    public function exchangeCodeForToken(string $code, string $state): array
    {
        $clientId = $this->getConfigValue('client_id');
        $clientSecret = $this->getConfigValue('client_secret');
        $redirectUri = $this->getConfigValue('redirect_uri');

        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $tenantId = $this->getConfigValue('tenant_id', 'common');
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->post($tokenUrl, $data);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $statusCode = $response->status();

                // Extract error message from response
                $errorMessage = $errorBody['error_description'] ?? $errorBody['error'] ?? 'Unknown error';

                // Provide more specific error messages
                if (isset($errorBody['error']) && $errorBody['error'] === 'invalid_client') {
                    // Check if the error is specifically about client secret
                    $errorDescription = $errorBody['error_description'] ?? '';
                    if (str_contains($errorDescription, 'client secret value, not the client secret ID')) {
                        throw new \Exception("Failed to exchange code for token: invalid_client. You are using the Secret ID instead of the Secret Value. In Azure Portal, go to Certificates & Secrets, and copy the 'Value' (not the 'Secret ID') of your client secret. The Value is shown only once when you create the secret, so if you don't have it, you'll need to create a new secret.");
                    }
                    throw new \Exception("Failed to exchange code for token: invalid_client. Please verify that your client_id and client_secret are correct in your .env file. Make sure you're using the Secret Value (not Secret ID) and that the client_secret has not expired in Azure Portal.");
                }

                $this->handleApiError($errorBody ?? ['error' => 'HTTP ' . $statusCode], 'exchange code for token');
            }

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("API request failed: " . $e->getMessage());
        }
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $data = [
            'client_id' => $this->getConfigValue('client_id'),
            'client_secret' => $this->getConfigValue('client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'scope' => 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/OnlineMeetings.ReadWrite https://graph.microsoft.com/Calendars.ReadWrite offline_access',
        ];

        $tenantId = $this->getConfigValue('tenant_id', 'common');
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->post($tokenUrl, $data);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $this->handleApiError($errorBody ?? ['error' => 'HTTP ' . $response->status()], 'refresh access token');
            }

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("API request failed: " . $e->getMessage());
        }
    }

    public function createMeeting(MentorSession $session): array
    {
        // Try to get user info first to verify the token works
        try {
            $userInfo = $this->getUserInfo($session->mentor->defaultVideoTool->access_token);
        } catch (\Exception $e) {
            // Continue anyway - the error might be specific to user info endpoint
        }

        // Try using /me/onlineMeetings endpoint first (simpler, requires only OnlineMeetings.ReadWrite)
        try {
            // Save original timezone before converting to UTC
            $originalTimezone = $session->scheduled_at->timezone->getName();
            
            // Convert to UTC before formatting for calendar API
            $startTime = $session->scheduled_at->copy()->utc()->format('Y-m-d\TH:i:s.000\Z');
            $endTime = $session->scheduled_at->copy()->addMinutes($session->duration_minutes)->utc()->format('Y-m-d\TH:i:s.000\Z');

            $meetingData = [
                'subject' => $session->title,
                'startDateTime' => $startTime,
                'endDateTime' => $endTime,
            ];

            $response = $this->makeAuthenticatedRequest(
                'post',
                '/me/onlineMeetings',
                $session->mentor->defaultVideoTool->access_token,
                $meetingData
            );

            if (isset($response['error'])) {
                throw new \Exception("Failed to create meeting using /me/onlineMeetings: " . ($response['error']['message'] ?? 'Unknown error'));
            }

            return $response;
        } catch (\Exception $e) {
            // Check if error is 403 Forbidden (Teams license issue)
            // If so, don't try fallback - this is a license/permission issue, not a token issue
            $is403Error = str_contains($e->getMessage(), '403') || 
                         str_contains($e->getMessage(), 'Forbidden') ||
                         str_contains($e->getMessage(), 'TeamsMeetingProcessorException');
            
            if ($is403Error) {
                // Re-throw the exception - don't try fallback for 403 errors
                throw $e;
            }

            // Fallback to /me/events endpoint (only for non-403 errors)
            // Save original timezone before converting to UTC
            $originalTimezone = $session->scheduled_at->timezone->getName();
            
            // Convert to UTC before formatting for calendar API
            $startTime = $session->scheduled_at->copy()->utc()->format('Y-m-d\TH:i:s.000\Z');
            $endTime = $session->scheduled_at->copy()->addMinutes($session->duration_minutes)->utc()->format('Y-m-d\TH:i:s.000\Z');

            $meetingData = [
                'subject' => $session->title,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $session->description ?? '',
                ],
                'start' => [
                    'dateTime' => $startTime,
                    'timeZone' => $originalTimezone,
                ],
                'end' => [
                    'dateTime' => $endTime,
                    'timeZone' => $originalTimezone,
                ],
                'isOnlineMeeting' => true,
                'onlineMeetingProvider' => 'teamsForBusiness',
            ];

            $response = $this->makeAuthenticatedRequest(
                'post',
                '/me/events',
                $session->mentor->defaultVideoTool->access_token,
                $meetingData
            );

            if (isset($response['error'])) {
                $this->handleApiError($response, 'create meeting');
            }

            return $response;
        }
    }

    public function updateMeeting(MentorSession $session): array
    {
        // Save original timezone before converting to UTC
        $originalTimezone = $session->scheduled_at->timezone->getName();
        
        // Convert to UTC before formatting for calendar API
        $startTime = $session->scheduled_at->copy()->utc()->format('Y-m-d\TH:i:s.000\Z');
        $endTime = $session->scheduled_at->copy()->addMinutes($session->duration_minutes)->utc()->format('Y-m-d\TH:i:s.000\Z');

        $meetingData = [
            'subject' => $session->title,
            'body' => [
                'contentType' => 'HTML',
                'content' => $session->description ?? '',
            ],
            'start' => [
                'dateTime' => $startTime,
                'timeZone' => $originalTimezone,
            ],
            'end' => [
                'dateTime' => $endTime,
                'timeZone' => $originalTimezone,
            ],
        ];

        $response = $this->makeAuthenticatedRequest(
            'patch',
            "/me/events/{$session->meeting_id}",
            $session->mentor->defaultVideoTool->access_token,
            $meetingData
        );

        if (isset($response['error'])) {
            $this->handleApiError($response, 'update meeting');
        }

        return $response;
    }

    public function deleteMeeting(string $meetingId, ?string $accessToken = null): bool
    {
        if (!$accessToken) {
            throw new \Exception("Access token is required to delete meeting");
        }

        try {
            $this->makeAuthenticatedRequest(
                'delete',
                "/me/events/{$meetingId}",
                $accessToken
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getMeetingDetails(string $meetingId, ?string $accessToken = null): array
    {
        if (!$accessToken) {
            throw new \Exception("Access token is required to get meeting details");
        }

        $response = $this->makeAuthenticatedRequest(
            'get',
            "/me/events/{$meetingId}",
            $accessToken
        );

        if (isset($response['error'])) {
            $this->handleApiError($response, 'get meeting details');
        }

        return $response;
    }

    public function validateToken(string $accessToken): bool
    {
        try {
            // Validate token by checking calendar access (requires Calendars.ReadWrite permission)
            // This ensures the token has the required permissions for creating events
            $this->makeAuthenticatedRequest('get', '/me/calendar', $accessToken);
            return true;
        } catch (\Exception $e) {
            // Re-throw exception so caller can handle it appropriately
            // This allows the caller to attempt token refresh if needed
            throw new \Exception("Token validation failed: " . $e->getMessage());
        }
    }

    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = $this->makeAuthenticatedRequest('get', '/me', $accessToken);

            if (isset($response['error'])) {
                $errorCode = $response['error']['code'] ?? null;
                $errorMessage = $response['error']['message'] ?? '';

                // Provide more specific error messages
                if ($errorCode === 'Authorization_RequestDenied') {
                    throw new \Exception("Insufficient privileges to access user info. Please ensure that the required API permissions (User.Read, OnlineMeetings.ReadWrite, Calendars.ReadWrite) are granted in Azure Portal and that admin consent is provided if required.");
                }

                $this->handleApiError($response, 'get user info');
            }

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createCalendarEvent(MentorSession $session): array
    {
        // Teams meetings are automatically created as calendar events
        return [
            'event_id' => $session->meeting_id,
            'calendar_type' => 'teams',
            'status' => 'created',
        ];
    }

    public function updateCalendarEvent(MentorSession $session): array
    {
        // Teams meetings are automatically updated as calendar events
        return [
            'event_id' => $session->meeting_id,
            'calendar_type' => 'teams',
            'status' => 'updated',
        ];
    }

    public function deleteCalendarEvent(string $eventId): bool
    {
        // Teams meetings are automatically deleted as calendar events
        return true;
    }
}

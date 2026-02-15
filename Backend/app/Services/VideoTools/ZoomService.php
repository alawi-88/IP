<?php

namespace App\Services\VideoTools;

use App\Models\MentorSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService extends BaseVideoToolService
{
    protected string $toolType = 'zoom';

    protected function getConfig(): array
    {
        return [
            'client_id' => config('video_tools.zoom.client_id'),
            'client_secret' => config('video_tools.zoom.client_secret'),
            'redirect_uri' => config('video_tools.zoom.redirect_uri'),
            'base_url' => config('video_tools.zoom.base_url', 'https://api.zoom.us/v2'),
            'account_id' => config('video_tools.zoom.account_id'),
        ];
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->getConfigValue('client_id'),
            'redirect_uri' => $this->getConfigValue('redirect_uri'),
            'state' => $state,
        ];

        return 'https://zoom.us/oauth/authorize?' . http_build_query($params);
    }

    public function exchangeCodeForToken(string $code, string $state): array
    {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->getConfigValue('redirect_uri'),
        ];

        // Zoom OAuth API requires form-urlencoded, not JSON
        // Zoom uses Basic Auth with client_id:client_secret in Authorization header
        $response = Http::asForm()
            ->withBasicAuth(
                $this->getConfigValue('client_id'),
                $this->getConfigValue('client_secret')
            )
            ->post('https://zoom.us/oauth/token', $data);

        if (!$response->successful()) {
            $error = $response->json();
            if (isset($error['error']) || isset($error['reason'])) {
                $this->handleApiError($error, 'exchange code for token');
            }
            throw new \Exception("API request failed: " . $response->body());
        }

        return $response->json();
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        // Zoom OAuth API requires form-urlencoded, not JSON
        // Zoom uses Basic Auth with client_id:client_secret in Authorization header
        $response = Http::asForm()
            ->withBasicAuth(
                $this->getConfigValue('client_id'),
                $this->getConfigValue('client_secret')
            )
            ->post('https://zoom.us/oauth/token', $data);

        if (!$response->successful()) {
            $error = $response->json();
            if (isset($error['error']) || isset($error['reason'])) {
                $this->handleApiError($error, 'refresh access token');
            }
            throw new \Exception("API request failed: " . $response->body());
        }

        return $response->json();
    }

    public function createMeeting(MentorSession $session): array
    {
        // Save original timezone before converting to UTC
        $originalTimezone = $session->scheduled_at->timezone->getName();
        
        // Convert to UTC before formatting for calendar API
        $startTime = $session->scheduled_at->copy()->utc()->format('Y-m-d\TH:i:s\Z');
        $duration = $session->duration_minutes;

        $meetingData = [
            'topic' => $session->title,
            'type' => 2, // Scheduled meeting
            'start_time' => $startTime,
            'duration' => $duration,
            'timezone' => $originalTimezone,
            'agenda' => $session->description ?? '',
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => false,
                'waiting_room' => true,
                'auto_recording' => 'none',
                'enforce_login' => false,
            ],
        ];

        $response = $this->makeAuthenticatedRequest(
            'post',
            '/users/me/meetings',
            $session->mentor->defaultVideoTool->access_token,
            $meetingData
        );

        if (isset($response['error'])) {
            $this->handleApiError($response, 'create meeting');
        }

        return $response;
    }

    public function updateMeeting(MentorSession $session): array
    {
        // Save original timezone before converting to UTC
        $originalTimezone = $session->scheduled_at->timezone->getName();
        
        // Convert to UTC before formatting for calendar API
        $startTime = $session->scheduled_at->copy()->utc()->format('Y-m-d\TH:i:s\Z');
        $duration = $session->duration_minutes;

        $meetingData = [
            'topic' => $session->title,
            'type' => 2,
            'start_time' => $startTime,
            'duration' => $duration,
            'timezone' => $originalTimezone,
            'agenda' => $session->description ?? '',
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => false,
                'waiting_room' => true,
                'auto_recording' => 'none',
                'enforce_login' => false,
            ],
        ];

        $response = $this->makeAuthenticatedRequest(
            'patch',
            "/meetings/{$session->meeting_id}",
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
            $response = $this->makeAuthenticatedRequest(
                'delete',
                "/meetings/{$meetingId}",
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
            "/meetings/{$meetingId}",
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
            $this->makeAuthenticatedRequest('get', '/users/me', $accessToken);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = $this->makeAuthenticatedRequest('get', '/users/me', $accessToken);

        if (isset($response['error'])) {
            $this->handleApiError($response, 'get user info');
        }

        return $response;
    }

    public function createCalendarEvent(MentorSession $session): array
    {
        // Zoom doesn't have a separate calendar API, meetings are automatically added to calendar
        // This method is kept for consistency with the interface
        return [
            'event_id' => $session->meeting_id,
            'calendar_type' => 'zoom',
            'status' => 'created',
        ];
    }

    public function updateCalendarEvent(MentorSession $session): array
    {
        // Zoom doesn't have a separate calendar API
        return [
            'event_id' => $session->meeting_id,
            'calendar_type' => 'zoom',
            'status' => 'updated',
        ];
    }

    public function deleteCalendarEvent(string $eventId): bool
    {
        // Zoom doesn't have a separate calendar API
        return true;
    }
}

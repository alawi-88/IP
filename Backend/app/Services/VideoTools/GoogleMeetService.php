<?php

namespace App\Services\VideoTools;

use App\Models\MentorSession;
use Carbon\Carbon;

class GoogleMeetService extends BaseVideoToolService
{
    protected string $toolType = 'google_meet';

    protected function getConfig(): array
    {
        return [
            'client_id' => config('video_tools.google.client_id'),
            'client_secret' => config('video_tools.google.client_secret'),
            'redirect_uri' => config('video_tools.google.redirect_uri'),
            'base_url' => config('video_tools.google.base_url', 'https://www.googleapis.com'),
            'calendar_api_url' => 'https://www.googleapis.com/calendar/v3',
        ];
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->getConfigValue('client_id'),
            'redirect_uri' => $this->getConfigValue('redirect_uri'),
            'scope' => 'https://www.googleapis.com/auth/calendar  https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function exchangeCodeForToken(string $code, string $state): array
    {
        $data = [
            'client_id' => $this->getConfigValue('client_id'),
            'client_secret' => $this->getConfigValue('client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getConfigValue('redirect_uri'),
        ];

        // Google OAuth API requires form-urlencoded, not JSON
        $response = \Illuminate\Support\Facades\Http::asForm()
            ->post('https://oauth2.googleapis.com/token', $data);

        if (!$response->successful()) {
            $error = $response->json();
            if (isset($error['error'])) {
                $this->handleApiError($error, 'exchange code for token');
            }
            throw new \Exception("API request failed: " . $response->body());
        }

        return $response->json();
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $data = [
            'client_id' => $this->getConfigValue('client_id'),
            'client_secret' => $this->getConfigValue('client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        // Google OAuth API requires form-urlencoded, not JSON
        $response = \Illuminate\Support\Facades\Http::asForm()
            ->post('https://oauth2.googleapis.com/token', $data);

        if (!$response->successful()) {
            $error = $response->json();
            if (isset($error['error'])) {
                // Preserve error type in exception message for better error detection
                $errorType = $error['error'];
                $errorDescription = $error['error_description'] ?? '';
                $errorMessage = "Failed to refresh access token: {$errorType}";
                if ($errorDescription) {
                    $errorMessage .= " - {$errorDescription}";
                }
                throw new \Exception($errorMessage);
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
        $endTime = $session->scheduled_at->copy()->addMinutes($session->duration_minutes)->utc()->format('Y-m-d\TH:i:s\Z');

        // Ensure relationships are loaded
        if (!$session->relationLoaded('mentor')) {
            $session->load('mentor');
        }
        if (!$session->relationLoaded('participant')) {
            $session->load('participant');
        }

        // Build attendees list
        $attendees = [];
        
        // Add mentor email if available
        if ($session->mentor && $session->mentor->email) {
            $attendees[] = [
                'email' => $session->mentor->email,
            ];
        }
        
        // Add participant email if available
        if ($session->participant && $session->participant->email) {
            $attendees[] = [
                'email' => $session->participant->email,
            ];
        }

        $meetingData = [
            'summary' => $session->title,
            'description' => $session->description ?? '',
            'start' => [
                'dateTime' => $startTime,
                'timeZone' => $originalTimezone,
            ],
            'end' => [
                'dateTime' => $endTime,
                'timeZone' => $originalTimezone,
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid(),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ];

        // Add attendees if any
        if (!empty($attendees)) {
            $meetingData['attendees'] = $attendees;
        }

        // Build query parameters
        $queryParams = ['conferenceDataVersion' => '1'];
        if (!empty($attendees)) {
            $queryParams['sendUpdates'] = 'all'; // Send email notifications to all attendees
        }
        $queryString = http_build_query($queryParams);

        $response = $this->makeAuthenticatedRequest(
            'post',
            "/calendar/v3/calendars/primary/events?{$queryString}",
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
        $endTime = $session->scheduled_at->copy()->addMinutes($session->duration_minutes)->utc()->format('Y-m-d\TH:i:s\Z');

        // Ensure relationships are loaded
        if (!$session->relationLoaded('mentor')) {
            $session->load('mentor');
        }
        if (!$session->relationLoaded('participant')) {
            $session->load('participant');
        }

        // Build attendees list
        $attendees = [];
        
        // Add mentor email if available
        if ($session->mentor && $session->mentor->email) {
            $attendees[] = [
                'email' => $session->mentor->email,
            ];
        }
        
        // Add participant email if available
        if ($session->participant && $session->participant->email) {
            $attendees[] = [
                'email' => $session->participant->email,
            ];
        }

        $meetingData = [
            'summary' => $session->title,
            'description' => $session->description ?? '',
            'start' => [
                'dateTime' => $startTime,
                'timeZone' => $originalTimezone,
            ],
            'end' => [
                'dateTime' => $endTime,
                'timeZone' => $originalTimezone,
            ],
        ];

        // Add attendees if any
        if (!empty($attendees)) {
            $meetingData['attendees'] = $attendees;
        }

        // Build query parameters
        $queryParams = [];
        if (!empty($attendees)) {
            $queryParams['sendUpdates'] = 'all'; // Send email notifications to all attendees
        }
        $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';

        $response = $this->makeAuthenticatedRequest(
            'patch',
            "/calendar/v3/calendars/primary/events/{$session->meeting_id}{$queryString}",
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
                "/calendar/v3/calendars/primary/events/{$meetingId}",
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
            "/calendar/v3/calendars/primary/events/{$meetingId}",
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
            $this->makeAuthenticatedRequest('get', 'https://www.googleapis.com/oauth2/v2/userinfo', $accessToken);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = $this->makeAuthenticatedRequest('get', 'https://www.googleapis.com/oauth2/v2/userinfo', $accessToken);

        if (isset($response['error'])) {
            $this->handleApiError($response, 'get user info');
        }

        return $response;
    }

    public function createCalendarEvent(MentorSession $session): array
    {
        // Google Meet meetings are automatically created as calendar events
        return [
            'event_id' => $session->meeting_id,
            'calendar_type' => 'google',
            'status' => 'created',
        ];
    }

    public function updateCalendarEvent(MentorSession $session): array
    {
        // Google Meet meetings are automatically updated as calendar events
        return [
            'event_id' => $session->meeting_id,
            'calendar_type' => 'google',
            'status' => 'updated',
        ];
    }

    public function deleteCalendarEvent(string $eventId): bool
    {
        // Google Meet meetings are automatically deleted as calendar events
        return true;
    }
}

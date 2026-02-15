<?php

namespace App\Services;

use App\Models\Mentor;
use App\Models\MentorVideoTool;
use App\Models\MentorSession;
use App\Services\VideoTools\ZoomService;
use App\Services\VideoTools\TeamsService;
use App\Services\VideoTools\GoogleMeetService;
use App\Services\VideoTools\BaseVideoToolService;
use Illuminate\Support\Str;

class VideoToolIntegrationService
{
    protected array $services = [];

    public function __construct()
    {
        $this->services = [
            'zoom' => new ZoomService(),
            'teams' => new TeamsService(),
            'google_meet' => new GoogleMeetService(),
        ];
    }

    /**
     * Resolve the video tool integration that should be used for a mentor.
     *
     * If a global Google Meet integration is configured, it takes precedence.
     */
    public function resolveVideoToolForMentor(int $mentorId, ?bool $useGlobalAccount = null): ?MentorVideoTool
    {
        $shouldUseGlobal = $useGlobalAccount ?? (bool) config('video_tools.google.use_global_account', false);

        if ($shouldUseGlobal) {
            $forcedEmail = config('video_tools.google.default_account_email');
            $forcedMentorId = config('video_tools.google.default_account_mentor_id');

            if ($forcedEmail || $forcedMentorId) {
                $globalGoogleTool = MentorVideoTool::getGlobalDefault(
                    'google_meet',
                    $forcedEmail,
                    $forcedMentorId ? (int) $forcedMentorId : null
                );

                if ($globalGoogleTool) {
                    return $globalGoogleTool;
                }
            }
        }

        return MentorVideoTool::getDefaultForMentor($mentorId);
    }

    /**
     * Ensure the session's mentor relation has the resolved video tool attached.
     */
    protected function attachVideoToolToSessionMentor(MentorSession $session, MentorVideoTool $videoTool): void
    {
        if (!$session->relationLoaded('mentor')) {
            $session->load('mentor');
        }

        if ($session->mentor) {
            $session->mentor->setRelation('defaultVideoTool', $videoTool);
            $session->setRelation('mentor', $session->mentor);
        }
    }

    /**
     * Get the service for a specific video tool.
     */
    public function getService(string $toolType): ?BaseVideoToolService
    {
        return $this->services[$toolType] ?? null;
    }

    /**
     * Get authorization URL for a video tool.
     */
    public function getAuthorizationUrl(string $toolType, int $mentorId, ?string $redirectUri = null): string
    {
        $service = $this->getService($toolType);

        if (!$service) {
            throw new \InvalidArgumentException("Unsupported video tool: {$toolType}");
        }

        if (!$service->isConfigured()) {
            throw new \Exception("Video tool {$toolType} is not properly configured");
        }

        $state = $this->generateState($mentorId, $toolType, $redirectUri);

        return $service->getAuthorizationUrl($state);
    }

    /**
     * Handle OAuth callback and create video tool integration.
     */
    public function handleCallback(string $toolType, string $code, string $state): MentorVideoTool
    {
        $service = $this->getService($toolType);

        if (!$service) {
            throw new \InvalidArgumentException("Unsupported video tool: {$toolType}");
        }

        // Validate state
        $stateData = $this->validateState($state);
        if (!$stateData || $stateData['tool_type'] !== $toolType) {
            throw new \Exception("Invalid state parameter");
        }

        $mentorId = $stateData['mentor_id'];

        // Exchange code for token
        $tokenData = $service->exchangeCodeForToken($code, $state);

        // Get user info
        $userInfo = $service->getUserInfo($tokenData['access_token']);

        // Check if there's already a tool integration for this mentor and tool type
        $existingTool = MentorVideoTool::where('mentor_id', $mentorId)
            ->where('tool_type', $toolType)
            ->first();

        // Check if there's already a default tool for this mentor
        $hasDefault = MentorVideoTool::where('mentor_id', $mentorId)
            ->where('is_default', true)
            ->exists();

        // Determine if this should be the default tool
        // Only if there's no default tool yet
        $shouldBeDefault = !$hasDefault;

        if ($existingTool) {
            // Update existing tool
            $videoTool = $existingTool;

            // Preserve existing refresh_token if new one is not provided
            // Google may not return refresh_token if it was already granted
            $updateData = [
                'account_id' => $this->extractAccountId($toolType, $userInfo),
                'account_email' => $this->extractAccountEmail($toolType, $userInfo),
                'access_token' => $tokenData['access_token'],
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : null,
                'is_active' => true,
                'last_sync_at' => now(),
            ];

            // Only update refresh_token if a new one is provided
            if (isset($tokenData['refresh_token']) && !empty($tokenData['refresh_token'])) {
                $updateData['refresh_token'] = $tokenData['refresh_token'];
            }

            $videoTool->update($updateData);

            // Refresh the model to get updated data
            $videoTool->refresh();

            // If this should be the default and it's not already set, make it default
            if ($shouldBeDefault && !$videoTool->is_default) {
                $videoTool->setAsDefault();
            }
        } else {
            // Create new tool integration
            // The unique constraint on ['mentor_id', 'tool_type'] ensures each tool can only be added once per mentor
            // This allows multiple tools (Zoom, Teams, Google Meet) for the same mentor

            // Create new tool
            $videoTool = MentorVideoTool::create([
                'mentor_id' => $mentorId,
                'tool_type' => $toolType,
                'account_id' => $this->extractAccountId($toolType, $userInfo),
                'account_email' => $this->extractAccountEmail($toolType, $userInfo),
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : null,
                'is_active' => true,
                'is_default' => $shouldBeDefault,
                'last_sync_at' => now(),
            ]);

            // If this should be the default and it's not already set, make it default
            // This ensures only one tool is default per mentor (handled by setAsDefault method)
            if ($shouldBeDefault && !$videoTool->is_default) {
                $videoTool->setAsDefault();
            }
        }

        // Note: We don't validate the token immediately after authorization
        // because the token may be valid but the permissions may not be granted yet
        // The token will be validated when it's actually used (e.g., creating a meeting)
        // If the token is invalid at that point, it will be handled appropriately

        return $videoTool;
    }

    /**
     * Refresh access token for a video tool integration.
     */
    public function refreshToken(MentorVideoTool $videoTool): bool
    {
        $service = $this->getService($videoTool->tool_type);

        if (!$service || !$videoTool->refresh_token) {
            \Log::warning("Cannot refresh token: service or refresh_token missing", [
                'mentor_id' => $videoTool->mentor_id,
                'tool_type' => $videoTool->tool_type,
                'has_service' => $service !== null,
                'has_refresh_token' => $videoTool->refresh_token !== null,
            ]);
            return false;
        }

        try {
            $tokenData = $service->refreshAccessToken($videoTool->refresh_token);

            $videoTool->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $videoTool->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : null,
                'last_sync_at' => now(),
            ]);

            // Refresh the model to get updated data
            $videoTool->refresh();

            return true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $isInvalidGrant = str_contains(strtolower($errorMessage), 'invalid_grant') || 
                             str_contains(strtolower($errorMessage), 'invalid grant');
            
            \Log::error("Failed to refresh token", [
                'mentor_id' => $videoTool->mentor_id,
                'tool_type' => $videoTool->tool_type,
                'account_email' => $videoTool->account_email,
                'error' => $errorMessage,
                'is_invalid_grant' => $isInvalidGrant,
                'trace' => $e->getTraceAsString(),
            ]);

            // If refresh token is invalid/expired (invalid_grant), mark the video tool as inactive
            // This prevents repeated failed refresh attempts
            if ($isInvalidGrant) {
                try {
                    $videoTool->update(['is_active' => false]);
                    \Log::warning("Marked video tool as inactive due to invalid refresh token", [
                        'mentor_id' => $videoTool->mentor_id,
                        'tool_type' => $videoTool->tool_type,
                        'account_email' => $videoTool->account_email,
                        'video_tool_id' => $videoTool->id,
                    ]);
                } catch (\Exception $updateException) {
                    \Log::error("Failed to mark video tool as inactive", [
                        'mentor_id' => $videoTool->mentor_id,
                        'tool_type' => $videoTool->tool_type,
                        'error' => $updateException->getMessage(),
                    ]);
                }
            }

            return false;
        }
    }

    /**
     * Create a session with video tool integration.
     */
    public function createSession(MentorSession $session): array
    {
        $videoTool = $this->resolveVideoToolForMentor($session->mentor_id);

        if (!$videoTool) {
            throw new \Exception("No default video tool configured for mentor");
        }

        $service = $this->getService($videoTool->tool_type);

        if (!$service) {
            throw new \Exception("Unsupported video tool: {$videoTool->tool_type}");
        }

        // Make sure session->mentor uses the resolved video tool (even if shared)
        $this->attachVideoToolToSessionMentor($session, $videoTool);

        // Check if video tool is inactive (may have been marked inactive due to invalid refresh token)
        if (!$videoTool->is_active) {
            throw new \Exception("Video tool integration is inactive. The refresh token may have expired or been revoked. Please re-authorize the video tool integration for the account: " . ($videoTool->account_email ?? 'the configured account'));
        }

        // Check if token needs refresh
        if ($videoTool->isTokenExpired()) {
            if (!$videoTool->refresh_token) {
                // Token expired and no refresh token - cannot proceed
                throw new \Exception("Access token has expired and no refresh token is available. Please re-authorize the video tool integration for the account: " . ($videoTool->account_email ?? 'the configured account'));
            } else {
                // Try to refresh token
                $refreshSuccess = $this->refreshToken($videoTool);
                if ($refreshSuccess) {
                    $videoTool->refresh();
                } else {
                    // Refresh failed - check if tool was marked inactive during refresh
                    $videoTool->refresh();
                    if (!$videoTool->is_active) {
                        throw new \Exception("Access token has expired and the refresh token is invalid or expired. The video tool integration has been deactivated. Please re-authorize the video tool integration for the account: " . ($videoTool->account_email ?? 'the configured account'));
                    }
                    
                    // Refresh failed for other reasons - cannot proceed
                    throw new \Exception("Access token has expired and refresh failed. Please re-authorize the video tool integration for the account: " . ($videoTool->account_email ?? 'the configured account'));
                }
            }
        }

        // Verify token exists before proceeding
        if (!$videoTool->is_active || !$videoTool->access_token) {
            throw new \Exception("Video tool integration is not active or has no access token. Please re-authorize the video tool integration.");
        }

        // Validate token by making a test API call (for Teams and Google Meet to catch invalid tokens early)
        // This helps catch cases where token appears valid but is actually invalid
        if (in_array($videoTool->tool_type, ['teams', 'google_meet'])) {
            try {
                $service->validateToken($videoTool->access_token);
            } catch (\Exception $validationException) {
                // If validation fails, try to refresh token if available
                if ($videoTool->refresh_token) {
                    if ($this->refreshToken($videoTool)) {
                        $videoTool->refresh();
                        // Retry validation after refresh
                        try {
                            $service->validateToken($videoTool->access_token);
                        } catch (\Exception $retryValidationException) {
                            throw new \Exception("Access token is invalid and refresh did not resolve the issue. Please re-authorize the video tool integration for the account: " . ($videoTool->account_email ?? 'the configured account'));
                        }
                    } else {
                        throw new \Exception("Access token is invalid and refresh failed. Please re-authorize the video tool integration for the account: " . ($videoTool->account_email ?? 'the configured account'));
                    }
                } else {
                    throw new \Exception("Access token is invalid and no refresh token is available. Please re-authorize the video tool integration for the account: " . ($videoTool->account_email ?? 'the configured account'));
                }
            }
        }

        try {
            // Create meeting
            $meetingData = $service->createMeeting($session);

            // Extract meeting details
            $meetingId = $this->extractMeetingId($videoTool->tool_type, $meetingData);
            $joinUrl = $this->extractJoinUrl($videoTool->tool_type, $meetingData);
            $password = $this->extractPassword($videoTool->tool_type, $meetingData);
            $calendarEventId = $this->extractCalendarEventId($videoTool->tool_type, $meetingData);

            // If join_url is missing but meeting_id exists, try to get it from meeting details
            if (empty($joinUrl) && !empty($meetingId)) {
                // Try to get join_url from meeting details
                try {
                    $meetingDetails = $service->getMeetingDetails($meetingId, $videoTool->access_token);
                    $joinUrlFromDetails = $this->extractJoinUrl($videoTool->tool_type, $meetingDetails);
                    if (!empty($joinUrlFromDetails)) {
                        $joinUrl = $joinUrlFromDetails;
                    }
                } catch (\Exception $detailsException) {
                    // Failed to get join_url from meeting details
                }
            }

            // Update session with meeting details
            $updateData = [
                'video_tool' => $videoTool->tool_type,
                'meeting_id' => $meetingId ?: null,
                'join_url' => $joinUrl ?: null,
                'password' => $password,
                'calendar_event_id' => $calendarEventId ?: null,
            ];

            $session->update($updateData);

            return $meetingData;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Check if error is invalid_grant (refresh token invalid/expired)
            if (str_contains($errorMessage, 'invalid_grant')) {
                // Refresh token is invalid - mark video tool as inactive and inform user
                \Log::error("Invalid grant error - refresh token is invalid or expired", [
                    'mentor_id' => $session->mentor_id,
                    'tool_type' => $videoTool->tool_type,
                    'error' => $errorMessage,
                ]);

                // Mark video tool as inactive
                try {
                    $videoTool->update(['is_active' => false]);
                } catch (\Exception $updateException) {
                    // Failed to mark video tool as inactive
                }

                throw new \Exception("Your video tool integration token has expired and cannot be refreshed. Please re-authorize the video tool integration by disconnecting and reconnecting it.");
            }

            // Check if error is 401 Unauthorized (token invalid)
            // The error message now includes status code: "API request failed with status 401"
            if (str_contains($errorMessage, 'status 401') || str_contains($errorMessage, '401') || str_contains($errorMessage, 'Unauthorized')) {
                // Don't try to refresh if we already know refresh_token is invalid (invalid_grant)
                // Check if refresh_token exists and hasn't failed with invalid_grant before
                if ($videoTool->refresh_token) {
                    // Try to refresh token if available
                    $refreshSuccess = $this->refreshToken($videoTool);
                    if ($refreshSuccess) {
                        $videoTool->refresh();

                        // Retry creating meeting with refreshed token
                        try {
                            $meetingData = $service->createMeeting($session);

                            $session->update([
                                'video_tool' => $videoTool->tool_type,
                                'meeting_id' => $this->extractMeetingId($videoTool->tool_type, $meetingData),
                                'join_url' => $this->extractJoinUrl($videoTool->tool_type, $meetingData),
                                'password' => $this->extractPassword($videoTool->tool_type, $meetingData),
                                'calendar_event_id' => $this->extractCalendarEventId($videoTool->tool_type, $meetingData),
                            ]);

                            return $meetingData;
                        } catch (\Exception $retryException) {
                            $retryErrorMessage = $retryException->getMessage();

                            // Check if retry error is invalid_grant
                            if (str_contains($retryErrorMessage, 'invalid_grant')) {
                                // Mark video tool as inactive
                                try {
                                    $videoTool->update(['is_active' => false]);
                                } catch (\Exception $updateException) {
                                    // Failed to mark video tool as inactive
                                }

                                throw new \Exception("Your video tool integration token has expired and cannot be refreshed. Please re-authorize the video tool integration by disconnecting and reconnecting it.");
                            }

                            // If the retry error is also 401, it's likely a permissions issue
                            $isPermissionIssue = str_contains($retryErrorMessage, '401') ||
                                                str_contains($retryErrorMessage, 'Unauthorized') ||
                                                str_contains($retryErrorMessage, 'permissions');

                            if ($isPermissionIssue) {
                                throw new \Exception("API request failed with status 401. Unauthorized access. The token may not have the required permissions. Please ensure that the required API permissions (User.Read, OnlineMeetings.ReadWrite, Calendars.ReadWrite) are granted in Azure Portal and that admin consent is provided if required.");
                            }

                            throw new \Exception("Access token is invalid and refresh failed. Please re-authorize the video tool integration.");
                        }
                    } else {
                        // Refresh failed - check if it was due to invalid_grant
                        // If refresh_token failed, mark tool as inactive
                        try {
                            $videoTool->update(['is_active' => false]);
                        } catch (\Exception $updateException) {
                            // Failed to mark video tool as inactive
                        }

                        throw new \Exception("Your video tool integration token has expired and cannot be refreshed. Please re-authorize the video tool integration by disconnecting and reconnecting it.");
                    }
                } else {
                    throw new \Exception("Access token is invalid and no refresh token is available. Please re-authorize the video tool integration.");
                }
            }

            throw $e;
        }
    }

    /**
     * Update a session with video tool integration.
     */
    public function updateSession(MentorSession $session): array
    {
        if (!$session->meeting_id) {
            throw new \Exception("Session does not have a meeting ID");
        }

        $videoTool = $this->resolveVideoToolForMentor($session->mentor_id);

        if (!$videoTool) {
            throw new \Exception("No video tool configured for updating the session");
        }

        $this->attachVideoToolToSessionMentor($session, $videoTool);

        $service = $this->getService($videoTool->tool_type);

        // Check if token needs refresh
        if ($videoTool->isTokenExpired()) {
            if (!$videoTool->refresh_token) {
                throw new \Exception("Access token has expired and no refresh token is available. Please re-authorize the video tool integration.");
            }

            if (!$this->refreshToken($videoTool)) {
                throw new \Exception("Failed to refresh access token. The refresh token may be invalid or expired. Please re-authorize the video tool integration.");
            }
            $videoTool->refresh();
        }

        // Verify token is valid before proceeding
        if (!$videoTool->isValid()) {
            throw new \Exception("Video tool integration is not valid. Please re-authorize the video tool integration.");
        }

        try {
            $meetingData = $service->updateMeeting($session);

            // Update session with new meeting details
            $session->update([
                'join_url' => $this->extractJoinUrl($videoTool->tool_type, $meetingData),
                'password' => $this->extractPassword($videoTool->tool_type, $meetingData),
            ]);

            return $meetingData;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get join URL for a session (called when session starts).
     */
    public function getSessionJoinUrl(MentorSession $session): ?string
    {
        if (!$session->meeting_id) {
            return null;
        }

        $videoTool = $this->resolveVideoToolForMentor($session->mentor_id);

        if (!$videoTool) {
            return null;
        }

        $this->attachVideoToolToSessionMentor($session, $videoTool);

        $service = $this->getService($videoTool->tool_type);
        if (!$service) {
            return null;
        }

        // Check if token needs refresh
        if ($videoTool->isTokenExpired()) {
            if (!$videoTool->refresh_token) {
                return null;
            }

            if (!$this->refreshToken($videoTool)) {
                return null;
            }
            $videoTool->refresh();
        }

        // Verify token is valid before proceeding
        if (!$videoTool->isValid()) {
            return null;
        }

        try {
            // Get meeting details to retrieve join_url
            $meetingData = $service->getMeetingDetails($session->meeting_id, $videoTool->access_token);

            // Extract join URL from meeting data
            $joinUrl = $this->extractJoinUrl($videoTool->tool_type, $meetingData);

            // Update session with join_url
            if ($joinUrl) {
                $session->update(['join_url' => $joinUrl]);
            }

            return $joinUrl;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Delete a session from video tool.
     */
    public function deleteSession(MentorSession $session): bool
    {
        if (!$session->meeting_id) {
            return true; // Nothing to delete
        }

        $videoTool = $this->resolveVideoToolForMentor($session->mentor_id);

        if (!$videoTool) {
            throw new \Exception("No video tool configured for deleting the session");
        }

        $this->attachVideoToolToSessionMentor($session, $videoTool);

        $service = $this->getService($videoTool->tool_type);

        // Check if token needs refresh
        if ($videoTool->isTokenExpired()) {
            if (!$videoTool->refresh_token) {
                throw new \Exception("Access token has expired and no refresh token is available. Please re-authorize the video tool integration.");
            }

            if (!$this->refreshToken($videoTool)) {
                throw new \Exception("Failed to refresh access token. The refresh token may be invalid or expired. Please re-authorize the video tool integration.");
            }
            $videoTool->refresh();
        }

        // Verify token is valid before proceeding
        if (!$videoTool->isValid()) {
            throw new \Exception("Video tool integration is not valid. Please re-authorize the video tool integration.");
        }

        try {
            return $service->deleteMeeting($session->meeting_id, $videoTool->access_token);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Disconnect a video tool integration.
     */
    public function disconnectTool(Mentor $mentor, string $toolType): bool
    {
        $videoTool = MentorVideoTool::where('mentor_id', $mentor->id)
            ->where('tool_type', $toolType)
            ->first();

        if (!$videoTool) {
            return false;
        }

        // If this was the default tool, set another tool as default
        if ($videoTool->is_default) {
            $otherTool = MentorVideoTool::where('mentor_id', $mentor->id)
                ->where('tool_type', '!=', $toolType)
                ->where('is_active', true)
                ->first();

            if ($otherTool) {
                $otherTool->setAsDefault();
            }
        }

        return $videoTool->delete();
    }

    /**
     * Generate state parameter for OAuth flow.
     */
    protected function generateState(int $mentorId, string $toolType, ?string $redirectUri = null): string
    {
        $stateData = [
            'mentor_id' => $mentorId,
            'tool_type' => $toolType,
            'timestamp' => now()->timestamp,
            'nonce' => Str::random(16),
        ];

        // Add redirect_uri to state if provided
        if ($redirectUri) {
            $stateData['redirect_uri'] = $redirectUri;
        }

        return base64_encode(json_encode($stateData));
    }

    /**
     * Validate state parameter.
     */
    protected function validateState(string $state): ?array
    {
        try {
            $stateData = json_decode(base64_decode($state), true);

            // Check if state is not too old (1 hour)
            if (now()->timestamp - $stateData['timestamp'] > 3600) {
                return null;
            }

            return $stateData;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract account ID from user info based on tool type.
     */
    protected function extractAccountId(string $toolType, array $userInfo): string
    {
        return match ($toolType) {
            'zoom' => $userInfo['id'] ?? '',
            'teams' => $userInfo['id'] ?? '',
            'google_meet' => $userInfo['id'] ?? '',
            default => '',
        };
    }

    /**
     * Extract account email from user info based on tool type.
     * Normalizes emails to lowercase to ensure consistent matching.
     * This is critical for @hotmail.com and other email domains to match correctly when joining meetings.
     * Video platforms (Teams, Google Meet, Zoom) require exact email matching (case-insensitive)
     * for authenticated users to avoid guest status and manual approval requirements.
     */
    protected function extractAccountEmail(string $toolType, array $userInfo): string
    {
        $email = match ($toolType) {
            'zoom' => $userInfo['email'] ?? '',
            'teams' => $userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? '',
            'google_meet' => $userInfo['email'] ?? '',
            default => '',
        };

        // Normalize email to lowercase for consistent matching
        // This ensures @hotmail.com and other email domains are matched correctly
        // when users join Teams meetings, preventing them from being treated as guests
        return $email ? strtolower(trim($email)) : '';
    }

    /**
     * Extract meeting ID from meeting data based on tool type.
     */
    protected function extractMeetingId(string $toolType, array $meetingData): string
    {
        return match ($toolType) {
            'zoom' => $meetingData['id'] ?? '',
            'teams' => $this->extractTeamsMeetingId($meetingData),
            'google_meet' => $meetingData['id'] ?? '',
            default => '',
        };
    }

    /**
     * Extract Teams meeting ID from meeting data.
     * Handles different response structures from /me/onlineMeetings and /me/events endpoints.
     */
    protected function extractTeamsMeetingId(array $meetingData): string
    {
        // Try direct id first (from /me/onlineMeetings endpoint)
        if (!empty($meetingData['id'])) {
            return $meetingData['id'];
        }

        // Try onlineMeeting.id (from /me/events endpoint)
        if (!empty($meetingData['onlineMeeting']['id'])) {
            return $meetingData['onlineMeeting']['id'];
        }

        // Try onlineMeeting object directly
        if (!empty($meetingData['onlineMeeting']) && is_array($meetingData['onlineMeeting'])) {
            $onlineMeeting = $meetingData['onlineMeeting'];
            if (!empty($onlineMeeting['id'])) {
                return $onlineMeeting['id'];
            }
        }

        return '';
    }

    /**
     * Extract join URL from meeting data based on tool type.
     */
    protected function extractJoinUrl(string $toolType, array $meetingData): string
    {
        return match ($toolType) {
            'zoom' => $meetingData['join_url'] ?? '',
            'teams' => $this->extractTeamsJoinUrl($meetingData),
            'google_meet' => $this->extractGoogleMeetJoinUrl($meetingData),
            default => '',
        };
    }

    /**
     * Extract Teams join URL from meeting data.
     * Handles different response structures from /me/onlineMeetings and /me/events endpoints.
     */
    protected function extractTeamsJoinUrl(array $meetingData): string
    {
        // Try direct joinWebUrl first (from /me/onlineMeetings endpoint)
        if (!empty($meetingData['joinWebUrl'])) {
            return $meetingData['joinWebUrl'];
        }

        // Try onlineMeeting.joinWebUrl (from /me/events endpoint)
        if (!empty($meetingData['onlineMeeting']['joinWebUrl'])) {
            return $meetingData['onlineMeeting']['joinWebUrl'];
        }

        // Try onlineMeeting.joinUrl (alternative field name)
        if (!empty($meetingData['onlineMeeting']['joinUrl'])) {
            return $meetingData['onlineMeeting']['joinUrl'];
        }

        // Try onlineMeeting object directly
        if (!empty($meetingData['onlineMeeting']) && is_array($meetingData['onlineMeeting'])) {
            $onlineMeeting = $meetingData['onlineMeeting'];
            if (!empty($onlineMeeting['joinWebUrl'])) {
                return $onlineMeeting['joinWebUrl'];
            }
            if (!empty($onlineMeeting['joinUrl'])) {
                return $onlineMeeting['joinUrl'];
            }
        }

        return '';
    }

    /**
     * Extract Google Meet join URL from meeting data.
     * Handles different response structures from Google Calendar API.
     */
    protected function extractGoogleMeetJoinUrl(array $meetingData): string
    {
        // Try direct hangoutLink first
        if (!empty($meetingData['hangoutLink'])) {
            return $meetingData['hangoutLink'];
        }

        // Try conferenceData.entryPoints (for Google Meet links)
        if (!empty($meetingData['conferenceData']['entryPoints']) && is_array($meetingData['conferenceData']['entryPoints'])) {
            foreach ($meetingData['conferenceData']['entryPoints'] as $entryPoint) {
                if (!empty($entryPoint['uri']) && str_contains($entryPoint['uri'], 'meet.google.com')) {
                    return $entryPoint['uri'];
                }
            }
        }

        // Try conferenceData.entryPoints[0].uri (first entry point)
        if (!empty($meetingData['conferenceData']['entryPoints'][0]['uri'])) {
            return $meetingData['conferenceData']['entryPoints'][0]['uri'];
        }

        return '';
    }

    /**
     * Extract password from meeting data based on tool type.
     */
    protected function extractPassword(string $toolType, array $meetingData): ?string
    {
        return match ($toolType) {
            'zoom' => $meetingData['password'] ?? null,
            'teams' => null, // Teams doesn't use passwords
            'google_meet' => null, // Google Meet doesn't use passwords
            default => null,
        };
    }

    /**
     * Extract calendar event ID from meeting data based on tool type.
     */
    protected function extractCalendarEventId(string $toolType, array $meetingData): array
    {
        return match ($toolType) {
            'zoom' => ['zoom' => $meetingData['id'] ?? ''],
            'teams' => ['teams' => $meetingData['id'] ?? ''],
            'google_meet' => ['google' => $meetingData['id'] ?? ''],
            default => [],
        };
    }

    /**
     * Get a sample of meeting data for logging (removes sensitive data and limits size).
     */
    protected function getMeetingDataSample(array $meetingData): array
    {
        $sample = [];
        $maxDepth = 3;
        $maxKeys = 20;

        $keys = array_slice(array_keys($meetingData), 0, $maxKeys);
        foreach ($keys as $key) {
            if (is_array($meetingData[$key])) {
                $sample[$key] = $this->getArraySample($meetingData[$key], $maxDepth - 1);
            } else {
                $sample[$key] = is_string($meetingData[$key]) && strlen($meetingData[$key]) > 100
                    ? substr($meetingData[$key], 0, 100) . '...'
                    : $meetingData[$key];
            }
        }

        return $sample;
    }

    /**
     * Get a sample of nested array for logging.
     */
    protected function getArraySample(array $data, int $maxDepth): array|string
    {
        if ($maxDepth <= 0) {
            return '[array]';
        }

        $sample = [];
        $keys = array_slice(array_keys($data), 0, 10);
        foreach ($keys as $key) {
            if (is_array($data[$key])) {
                $sample[$key] = $this->getArraySample($data[$key], $maxDepth - 1);
            } else {
                $sample[$key] = is_string($data[$key]) && strlen($data[$key]) > 100
                    ? substr($data[$key], 0, 100) . '...'
                    : $data[$key];
            }
        }

        return $sample;
    }
}

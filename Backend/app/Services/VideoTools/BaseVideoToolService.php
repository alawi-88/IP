<?php

namespace App\Services\VideoTools;

use App\Models\MentorVideoTool;
use App\Models\MentorSession;
use Illuminate\Support\Facades\Http;

abstract class BaseVideoToolService
{
    protected string $toolType;
    protected string $baseUrl;
    protected array $config;

    public function __construct()
    {
        $this->config = $this->getConfig();
        $this->baseUrl = $this->config['base_url'] ?? '';
    }

    /**
     * Get the configuration for this video tool.
     */
    abstract protected function getConfig(): array;

    /**
     * Generate OAuth authorization URL.
     */
    abstract public function getAuthorizationUrl(string $state): string;

    /**
     * Exchange authorization code for access token.
     */
    abstract public function exchangeCodeForToken(string $code, string $state): array;

    /**
     * Refresh access token using refresh token.
     */
    abstract public function refreshAccessToken(string $refreshToken): array;

    /**
     * Create a meeting/session.
     */
    abstract public function createMeeting(MentorSession $session): array;

    /**
     * Update a meeting/session.
     */
    abstract public function updateMeeting(MentorSession $session): array;

    /**
     * Delete a meeting/session.
     */
    abstract public function deleteMeeting(string $meetingId, ?string $accessToken = null): bool;

    /**
     * Get meeting details.
     */
    abstract public function getMeetingDetails(string $meetingId, ?string $accessToken = null): array;

    /**
     * Validate the access token.
     */
    abstract public function validateToken(string $accessToken): bool;

    /**
     * Get user information from the video tool.
     */
    abstract public function getUserInfo(string $accessToken): array;

    /**
     * Create calendar event for the session.
     */
    abstract public function createCalendarEvent(MentorSession $session): array;

    /**
     * Update calendar event for the session.
     */
    abstract public function updateCalendarEvent(MentorSession $session): array;

    /**
     * Delete calendar event for the session.
     */
    abstract public function deleteCalendarEvent(string $eventId): bool;

    /**
     * Make HTTP request to the video tool API.
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        // If endpoint is a full URL (starts with http:// or https://), use it directly
        // Otherwise, prepend baseUrl
        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            $url = $endpoint;
        } else {
            $url = $this->baseUrl . $endpoint;
        }
        
        $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        try {
            $httpClient = Http::withHeaders($headers)->timeout(30);
            
            // For GET requests, send data as query parameters
            // For other methods, send as JSON body
            if (strtolower($method) === 'get') {
                $response = $httpClient->get($url, $data);
            } else {
                $response = $httpClient->$method($url, $data);
            }

            if ($response->successful()) {
                $jsonResponse = $response->json();
                // Ensure we always return an array, even if json() returns null
                return is_array($jsonResponse) ? $jsonResponse : [];
            }

            throw new \Exception("API request failed: " . $response->body());

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Make authenticated HTTP request.
     */
    protected function makeAuthenticatedRequest(string $method, string $endpoint, string $accessToken, array $data = []): array
    {
        // If endpoint is a full URL, use it directly, otherwise prepend baseUrl
        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            $url = $endpoint;
        } else {
            $url = $this->baseUrl . $endpoint;
        }
        
        $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        try {
            // Log token details for debugging (without exposing the full token)
            $tokenPreview = $accessToken ? substr($accessToken, 0, 20) . '...' : 'null';
            
            // Decode JWT token to check scopes (for Teams/Microsoft Graph)
            $tokenScopes = null;
            if ($accessToken && $this->toolType === 'teams') {
                try {
                    $tokenParts = explode('.', $accessToken);
                    if (count($tokenParts) >= 2) {
                        $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);
                        $tokenScopes = $payload['scp'] ?? $payload['roles'] ?? null;
                    }
                } catch (\Exception $e) {
                    // Ignore JWT decode errors
                }
            }
            
            $httpClient = Http::withToken($accessToken)
                ->withHeaders($defaultHeaders)
                ->timeout(30);
            
            // For GET requests, send data as query parameters
            // For other methods, send as JSON body
            if (strtolower($method) === 'get') {
                $response = $httpClient->get($url, $data);
            } else {
                $response = $httpClient->$method($url, $data);
            }

            if ($response->successful()) {
                $jsonResponse = $response->json();
                // Ensure we always return an array, even if json() returns null
                return is_array($jsonResponse) ? $jsonResponse : [];
            }

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseJson = $response->json();
            
            // Try to parse response body as JSON if it's not already parsed
            if (empty($responseJson) && !empty($responseBody)) {
                try {
                    $responseJson = json_decode($responseBody, true);
                } catch (\Exception $e) {
                    // Ignore JSON parsing errors
                }
            }
            
            $errorCode = $responseJson['error']['code'] ?? null;
            $errorMessage = $responseJson['error']['message'] ?? null;
            
            // For 401 errors with empty response body, try to get more details from WWW-Authenticate header
            $wwwAuthenticate = $response->header('WWW-Authenticate');
            $errorDetails = null;
            if ($statusCode === 401 && empty($responseBody) && $wwwAuthenticate) {
                // Parse WWW-Authenticate header for error details
                if (preg_match('/error="([^"]+)"/', $wwwAuthenticate, $matches)) {
                    $errorDetails = $matches[1];
                }
                if (preg_match('/error_description="([^"]+)"/', $wwwAuthenticate, $matches)) {
                    $errorDetails = ($errorDetails ? $errorDetails . ' - ' : '') . $matches[1];
                }
            }
            
            // Include status code in exception message for better error handling
            $exceptionMessage = "API request failed with status {$statusCode}";
            
            // Add more specific error information if available
            if ($statusCode === 401) {
                $toolName = ucfirst(str_replace('_', ' ', $this->toolType));
                
                // For 401 errors, provide tool-specific guidance
                if ($errorCode === 'Authorization_RequestDenied') {
                    if ($this->toolType === 'teams') {
                        $exceptionMessage .= ". Authorization denied. The token may not have the required permissions. Please ensure that the required API permissions (User.Read, OnlineMeetings.ReadWrite, Calendars.ReadWrite) are granted in Azure Portal and that admin consent is provided if required.";
                    } elseif ($this->toolType === 'google_meet') {
                        $exceptionMessage .= ". Authorization denied. The token may not have the required permissions. Please ensure that Google Calendar API is enabled and the required scopes are granted.";
                    } else {
                        $exceptionMessage .= ". Authorization denied. The token may not have the required permissions. Please re-authorize the {$toolName} integration.";
                    }
                } elseif ($errorCode === 'InvalidAuthenticationToken' || $errorCode === 'TokenNotFound') {
                    $exceptionMessage .= ". Invalid or missing authentication token. Please re-authorize the {$toolName} integration.";
                } else {
                    if ($this->toolType === 'teams') {
                        $exceptionMessage .= ". Unauthorized access. The token may not have the required permissions. Please ensure that the required API permissions (User.Read, OnlineMeetings.ReadWrite, Calendars.ReadWrite) are granted in Azure Portal and that admin consent is provided if required.";
                    } elseif ($this->toolType === 'google_meet') {
                        $exceptionMessage .= ". Unauthorized access. The token may not have the required permissions. Please ensure that Google Calendar API is enabled and the required scopes are granted.";
                    } else {
                        $exceptionMessage .= ". Unauthorized access. The token may not have the required permissions. Please re-authorize the {$toolName} integration.";
                    }
                }
                
                if ($errorCode) {
                    $exceptionMessage .= " Error code: {$errorCode}";
                }
                
                if ($errorMessage) {
                    $exceptionMessage .= ". Error message: {$errorMessage}";
                }
            } elseif ($statusCode === 403) {
                // For 403 errors, provide tool-specific error messages
                $toolName = ucfirst(str_replace('_', ' ', $this->toolType));
                
                // Check if this is a Google Calendar API error
                if ($this->toolType === 'google_meet' && 
                    (str_contains(strtolower($errorMessage ?? ''), 'calendar api') || 
                     str_contains(strtolower($responseBody ?? ''), 'calendar api'))) {
                    $exceptionMessage .= ". Forbidden access. Google Calendar API has not been enabled for this project. Please enable Google Calendar API in Google Cloud Console";
                } elseif ($this->toolType === 'teams') {
                    // Teams-specific error message
                    if ($errorCode === 'Forbidden') {
                        $exceptionMessage .= ". Forbidden access. The user may not have a Microsoft Teams license or may not have permissions to create meetings. Please ensure that the user has a valid Microsoft Teams license and permissions to create online meetings.";
                    } else {
                        $exceptionMessage .= ". Forbidden access. The user may not have a Microsoft Teams license or may not have permissions to create meetings.";
                    }
                } else {
                    // Generic 403 error for other tools
                    $exceptionMessage .= ". Forbidden access. The user may not have the required permissions or the API may not be enabled for this {$toolName} account.";
                }
                
                if ($errorCode) {
                    $exceptionMessage .= " Error code: {$errorCode}";
                }
                
                if ($errorMessage) {
                    $exceptionMessage .= ". Error message: {$errorMessage}";
                }
            } elseif (isset($responseJson['error'])) {
                if ($errorCode) {
                    $exceptionMessage .= ". Error code: {$errorCode}";
                }
                
                if ($errorMessage) {
                    $exceptionMessage .= ". Error message: {$errorMessage}";
                }
            } elseif (!empty($responseBody)) {
                $exceptionMessage .= ": " . $responseBody;
            }
            
            throw new \Exception($exceptionMessage);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Handle API errors and throw appropriate exceptions.
     */
    protected function handleApiError(array $response, string $operation): void
    {
        $errorMessage = $response['error'] ?? $response['message'] ?? 'Unknown error';
        
        throw new \Exception("Failed to {$operation}: {$errorMessage}");
    }

    /**
     * Get the tool type.
     */
    public function getToolType(): string
    {
        return $this->toolType;
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['client_id']) && 
               !empty($this->config['client_secret']) && 
               !empty($this->config['redirect_uri']);
    }

    /**
     * Get configuration value.
     */
    protected function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}

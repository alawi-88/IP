<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NafathIamService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private int $tokenCacheTime;

    public function __construct()
    {
        $this->baseUrl = config('services.nafath_iam.base_url', 'https://stg-iam.naql.sa');
        $this->clientId = config('services.nafath_iam.client_id', 'innovation');
        $this->clientSecret = config('services.nafath_iam.client_secret', 'C074DA8D-52E6-4638-8DEE-3BEB94B6B2D1');
        $this->tokenCacheTime = config('services.nafath_iam.token_cache_time', 3600); // 1 hour
    }

    /**
     * Get access token using client credentials
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'nafath_iam_access_token';
        
        // Try to get token from cache first
        $token = Cache::get($cacheKey);
        if ($token) {
            return $token;
        }

        try {
            $response = Http::asForm()
                ->post($this->baseUrl . '/connect/token', [
                    'grant_type' => 'client_credentials',
                    'client_ID' => $this->clientId,
                    'Client_Secret' => $this->clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'] ?? null;
                
                if ($accessToken) {
                    // Cache the token for the specified time
                    $expiresIn = $data['expires_in'] ?? $this->tokenCacheTime;
                    Cache::put($cacheKey, $accessToken, now()->addSeconds($expiresIn - 60)); // Cache 1 minute less than expiry
                    return $accessToken;
                }
            }

            Log::error('Failed to get Nafath IAM access token', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Exception while getting Nafath IAM access token', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Make authenticated request to Nafath IAM API
     */
    public function makeRequest(string $endpoint, string $method = 'GET', array $data = []): ?array
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('No access token available for Nafath IAM request');
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->{strtolower($method)}($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Nafath IAM API request failed', [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Exception while making Nafath IAM API request', [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Test the connection
     */
    public function testConnection(): array
    {
        $token = $this->getAccessToken();
        
        return [
            'success' => $token !== null,
            'token' => $token ? 'Token obtained successfully' : 'Failed to obtain token',
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Clear cached token (useful for testing or when token is invalid)
     */
    public function clearTokenCache(): void
    {
        Cache::forget('nafath_iam_access_token');
    }
}

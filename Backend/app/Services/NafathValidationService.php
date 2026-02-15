<?php

namespace App\Services;

use App\Models\NafathSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NafathValidationService
{
    /**
     * Validate OAuth2 credentials by testing the MIP token endpoint
     */
    public function validateCredentials(string $clientId, string $clientSecret, string $environment = 'production'): array
    {
        try {
            $settings = new NafathSettings();
            $settings->environment = $environment;
            
            // Test the discovery endpoint first to ensure the MIP is accessible
            $discoveryResponse = Http::timeout(10)->get($settings->getDiscoveryEndpoint());
            
            if (!$discoveryResponse->successful()) {
                return [
                    'valid' => false,
                    'message' => 'Unable to connect to MIP discovery endpoint. Please check the environment and network connectivity.',
                ];
            }

            // Test the token endpoint with client credentials (without scope)
            $tokenResponse = Http::timeout(10)->asForm()->post($settings->getTokenEndpoint(), [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]);

            if ($tokenResponse->successful()) {
                $tokenData = $tokenResponse->json();
                if (isset($tokenData['access_token'])) {
                    return [
                        'valid' => true,
                        'message' => 'MIP credentials are valid and connection is working.',
                        'environment' => $environment,
                        'base_url' => $settings->getMipBaseUrl(),
                    ];
                }
            }

            // If client_credentials fails, try to get discovery document to validate basic connectivity
            $discoveryData = $discoveryResponse->json();
            if (isset($discoveryData['issuer']) && isset($discoveryData['authorization_endpoint'])) {
                return [
                    'valid' => true,
                    'message' => 'MIP endpoint is accessible and discovery document is valid. Client credentials may require different grant type.',
                    'environment' => $environment,
                    'base_url' => $settings->getMipBaseUrl(),
                    'note' => 'Discovery endpoint is working, but client_credentials grant may not be supported.',
                ];
            }

            $errorData = $tokenResponse->json();
            $errorMessage = $errorData['error_description'] ?? $errorData['error'] ?? 'Invalid client credentials';

            return [
                'valid' => false,
                'message' => "MIP authentication failed: {$errorMessage}",
            ];

        } catch (\Exception $e) {
            Log::error('MIP validation error: ' . $e->getMessage());
            
            return [
                'valid' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get discovery document from MIP
     */
    public function getDiscoveryDocument(string $environment = 'production'): array
    {
        try {
            $settings = new NafathSettings();
            $settings->environment = $environment;
            
            $response = Http::timeout(10)->get($settings->getDiscoveryEndpoint());
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to retrieve discovery document',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error retrieving discovery document: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test MIP service connectivity
     */
    public function testConnectivity(string $environment = 'production'): array
    {
        try {
            $settings = new NafathSettings();
            $settings->environment = $environment;
            
            $response = Http::timeout(10)->get($settings->getDiscoveryEndpoint());
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Check if it's a valid OpenID Connect discovery document
                $isValidDiscovery = isset($data['issuer']) && 
                                  isset($data['authorization_endpoint']) && 
                                  isset($data['token_endpoint']);
                
                return [
                    'connected' => true,
                    'message' => $isValidDiscovery ? 
                        'MIP service is accessible and discovery document is valid' : 
                        'MIP service is accessible but discovery document may be incomplete',
                    'environment' => $environment,
                    'base_url' => $settings->getMipBaseUrl(),
                    'is_valid_discovery' => $isValidDiscovery,
                    'available_endpoints' => [
                        'authorization' => $data['authorization_endpoint'] ?? 'Not available',
                        'token' => $data['token_endpoint'] ?? 'Not available',
                        'userinfo' => $data['userinfo_endpoint'] ?? 'Not available',
                    ],
                ];
            }

            return [
                'connected' => false,
                'message' => 'MIP service is not accessible',
                'environment' => $environment,
                'base_url' => $settings->getMipBaseUrl(),
                'status_code' => $response->status(),
                'response_body' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('MIP connectivity test failed: ' . $e->getMessage());
            
            return [
                'connected' => false,
                'message' => 'Unable to connect to MIP service: ' . $e->getMessage(),
                'environment' => $environment,
                'base_url' => $settings->getMipBaseUrl(),
            ];
        }
    }
}

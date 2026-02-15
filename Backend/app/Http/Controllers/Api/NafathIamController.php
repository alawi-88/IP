<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NafathIamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NafathIamController extends Controller
{
    protected NafathIamService $nafathIamService;

    public function __construct(NafathIamService $nafathIamService)
    {
        $this->nafathIamService = $nafathIamService;
    }

    /**
     * Test Nafath IAM connection
     */
    public function testConnection(): JsonResponse
    {
        $result = $this->nafathIamService->testConnection();
        
        return response()->json([
            'message' => 'Nafath IAM connection test',
            'data' => $result
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Get access token
     */
    public function getToken(): JsonResponse
    {
        $token = $this->nafathIamService->getAccessToken();
        
        if ($token) {
            return response()->json([
                'message' => 'Access token obtained successfully',
                'token' => $token,
                'timestamp' => now()->toISOString()
            ]);
        }

        return response()->json([
            'message' => 'Failed to obtain access token',
            'error' => 'Unable to authenticate with Nafath IAM service'
        ], 500);
    }

    /**
     * Clear token cache
     */
    public function clearTokenCache(): JsonResponse
    {
        $this->nafathIamService->clearTokenCache();
        
        return response()->json([
            'message' => 'Token cache cleared successfully',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Make a test API request
     */
    public function testApiRequest(Request $request): JsonResponse
    {
        $endpoint = $request->input('endpoint', '/api/test');
        $method = $request->input('method', 'GET');
        $data = $request->input('data', []);

        $result = $this->nafathIamService->makeRequest($endpoint, $method, $data);
        
        if ($result !== null) {
            return response()->json([
                'message' => 'API request successful',
                'data' => $result,
                'endpoint' => $endpoint,
                'method' => $method,
                'timestamp' => now()->toISOString()
            ]);
        }

        return response()->json([
            'message' => 'API request failed',
            'error' => 'Unable to make request to Nafath IAM API',
            'endpoint' => $endpoint,
            'method' => $method
        ], 500);
    }
}

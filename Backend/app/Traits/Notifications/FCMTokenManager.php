<?php

namespace App\Traits\Notifications;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FCMTokenManager {
    private $cacheFile;

    public function __construct() {
        $this->cacheFile = storage_path('app/token_cache.json'); // Store in storage/app directory
    }

    // Get the access token, checking cache first
    public function getAccessToken() {
        $cachedToken = $this->getCachedToken();

        if ($cachedToken && $cachedToken['expires_at'] > time()) {
            // If cached token is still valid, return it
            return $cachedToken['access_token'];
        }

        // If token is expired or not found, generate a new one
        $newToken = $this->generateNewAccessToken();

        if ($newToken) {
            $this->cacheToken($newToken);
            return $newToken['access_token'];
        }

        return null;
    }

    // Generate a new access token using Google Service Account
    private function generateNewAccessToken() {
        $jsonKeyFile = storage_path('innovation-demo-staging-36bf1b62e503.json');
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        // Create credentials object
        $credentials = new ServiceAccountCredentials($scopes, $jsonKeyFile);

        // Fetch the OAuth token
        $authToken = $credentials->fetchAuthToken();

        if ($authToken && isset($authToken['access_token'])) {
            return [
                'access_token' => $authToken['access_token'],
                'expires_at' => time() + $authToken['expires_in'], // 3600 seconds = 1 hour
            ];
        }

        return null;
    }

    // Cache the access token in a file
    private function cacheToken($tokenData) {
        // Store token and its expiration in a JSON file
        file_put_contents($this->cacheFile, json_encode($tokenData));
    }

    // Retrieve the cached token
    private function getCachedToken() {
        if (file_exists($this->cacheFile)) {
            $tokenData = file_get_contents($this->cacheFile);
            return json_decode($tokenData, true); // Return as associative array
        }
        return null;
    }
}


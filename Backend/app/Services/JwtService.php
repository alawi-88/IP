<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Log;

class JwtService
{
    private string $secretKey;
    private int $expirationTime;
    private string $algorithm;

    public function __construct()
    {
        $this->secretKey = config('jwt.secret', env('JWT_SECRET', 'your-secret-key'));
        $this->expirationTime = config('jwt.expiration', 3600); // 1 hour default
        $this->algorithm = 'HS256';
    }

    /**
     * Generate JWT token for user
     */
    public function generateToken($user, int $expirationMinutes = null): string
    {
        $expiration = $expirationMinutes ? now()->addMinutes($expirationMinutes) : now()->addSeconds($this->expirationTime);
        
        // Get name based on user type
        $name = $this->getNameFromUser($user);
        
        $payload = [
            'iss' => config('app.url'), // Issuer
            'aud' => config('app.url'), // Audience
            'iat' => now()->timestamp, // Issued at
            'exp' => $expiration->timestamp, // Expiration
            'sub' => $user->id, // Subject (user ID)
            'user_type' => $this->getUserType($user),
            'user_data' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $name,
            ]
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }
    
    /**
     * Get name from user based on user type
     */
    private function getNameFromUser($user): string
    {
        // Handle Mentor's translated name field
        if ($user instanceof \App\Models\Mentor && isset($user->name)) {
            if (is_array($user->name)) {
                return $user->name['en'] ?? $user->name['ar'] ?? '';
            }
            return $user->name;
        }
        
        // Handle other user types
        if (isset($user->name)) {
            return $user->name;
        }
        
        if (isset($user->first_name) && isset($user->last_name)) {
            return $user->first_name . ' ' . $user->last_name;
        }
        
        return '';
    }

    /**
     * Verify and decode JWT token
     */
    public function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            Log::warning('JWT token expired', [
                'token_preview' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (SignatureInvalidException $e) {
            Log::warning('JWT token signature invalid', [
                'token_preview' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::warning('JWT token verification failed', [
                'token_preview' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken(string $token): ?string
    {
        $payload = $this->verifyToken($token);
        
        if (!$payload) {
            return null;
        }

        // Get user from payload
        $user = $this->getUserFromPayload($payload);
        
        if (!$user) {
            return null;
        }

        // Generate new token
        return $this->generateToken($user);
    }

    /**
     * Get user type from user model
     */
    private function getUserType($user): string
    {
        // Check for Mentor first (before checking for Participant since both may extend same base class)
        if ($user instanceof \App\Models\Mentor) {
            return 'mentor';
        } elseif ($user instanceof \App\Models\Participant) {
            return 'participant';
        } elseif ($user instanceof \App\Models\Judge) {
            return 'judge';
        } elseif ($user instanceof \App\Models\User) {
            return 'admin';
        }
        
        return 'unknown';
    }

    /**
     * Get user model from JWT payload
     */
    private function getUserFromPayload(array $payload)
    {
        $userId = $payload['sub'] ?? null;
        $userType = $payload['user_type'] ?? 'participant';

        if (!$userId) {
            return null;
        }

        switch ($userType) {
            case 'participant':
                return \App\Models\Participant::find($userId);
            case 'judge':
                return \App\Models\Judge::find($userId);
            case 'mentor':
                return \App\Models\Mentor::find($userId);
            case 'admin':
                return \App\Models\User::find($userId);
            default:
                return null;
        }
    }

    /**
     * Get user from JWT token
     */
    public function getUserFromToken(string $token)
    {
        $payload = $this->verifyToken($token);
        
        if (!$payload) {
            return null;
        }

        return $this->getUserFromPayload($payload);
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(string $token): bool
    {
        $payload = $this->verifyToken($token);
        return $payload === null;
    }

    /**
     * Get token expiration time
     */
    public function getTokenExpiration(string $token): ?\DateTime
    {
        $payload = $this->verifyToken($token);
        
        if (!$payload || !isset($payload['exp'])) {
            return null;
        }

        return new \DateTime('@' . $payload['exp']);
    }
}

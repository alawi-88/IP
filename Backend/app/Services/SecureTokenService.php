<?php

namespace App\Services;

use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SecureTokenService
{
    /**
     * Create a secure token for the user
     */
    public function createSecureToken($user, string $name = 'auth_token', array $abilities = ['*'], $expiresAt = null)
    {
        // Use Laravel Sanctum's default token creation for compatibility
        $token = $user->createToken($name, $abilities, $expiresAt);
        
        // Return the plain text token (Laravel Sanctum format)
        $plainTextToken = $token->plainTextToken;

        return $plainTextToken;
    }
    
    /**
     * Revoke a specific token
     */
    public function revokeToken($tokenId)
    {
        $token = PersonalAccessToken::find($tokenId);
        
        if ($token) {
            $token->delete();

            return true;
        }
        
        return false;
    }
    
    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens($user)
    {
        $deletedCount = $user->tokens()->delete();

        return $deletedCount;
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens()
    {
        $expiredTokens = PersonalAccessToken::where('expires_at', '<', now())->get();
        
        foreach ($expiredTokens as $token) {
            $token->delete();
        }

        return $expiredTokens->count();
    }
    
    /**
     * Get token usage statistics
     */
    public function getTokenStats()
    {
        $totalTokens = PersonalAccessToken::count();
        $activeTokens = PersonalAccessToken::where(function($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        })->count();
        $expiredTokens = PersonalAccessToken::where('expires_at', '<', now())->count();
        
        return [
            'total' => $totalTokens,
            'active' => $activeTokens,
            'expired' => $expiredTokens
        ];
    }
}

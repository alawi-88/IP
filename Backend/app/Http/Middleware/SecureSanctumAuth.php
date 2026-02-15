<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class SecureSanctumAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Rate limiting for token validation attempts
        $key = 'token-validation:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            Log::warning('Too many token validation attempts', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'message' => 'Too many authentication attempts. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }
        
        RateLimiter::hit($key, 60); // 10 attempts per minute
        
        // Handle Laravel Sanctum token format: id|hash
        $tokenRecord = null;
        
        if (str_contains($token, '|')) {
            // Laravel Sanctum token format: id|hash
            $tokenParts = explode('|', $token, 2);
            if (count($tokenParts) !== 2) {
                return response()->json(['message' => 'Invalid token format.'], 401);
            }
            
            $tokenId = $tokenParts[0];
            $tokenHash = $tokenParts[1];
            
            // Validate token ID is numeric and reasonable
            if (!is_numeric($tokenId) || $tokenId <= 0 || $tokenId > 999999999) {
                Log::warning('Suspicious token ID detected', [
                    'ip' => $request->ip(),
                    'token_id' => $tokenId,
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json(['message' => 'Invalid token.'], 401);
            }
            
            // Find token by ID first (more efficient)
            $tokenRecord = PersonalAccessToken::find($tokenId);
            
            if (!$tokenRecord) {
                Log::warning('Token not found', [
                    'ip' => $request->ip(),
                    'token_id' => $tokenId,
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            
            // Verify the token hash matches (Laravel Sanctum stores hashed tokens)
            if (!Hash::check($tokenHash, $tokenRecord->token)) {
                Log::warning('Token hash mismatch', [
                    'ip' => $request->ip(),
                    'token_id' => $tokenId,
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json(['message' => 'Invalid token.'], 401);
            }
        } else {
            // Try to find by plain text match (fallback)
            $tokenRecord = PersonalAccessToken::where('token', $token)->first();
            
            if (!$tokenRecord) {
                Log::warning('Token not found', [
                    'ip' => $request->ip(),
                    'token_preview' => substr($token, 0, 10) . '...',
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        }
        
        // Check if token is expired
        if ($tokenRecord->expires_at && $tokenRecord->expires_at < now()) {
            return response()->json(['message' => 'Token expired.'], 401);
        }
        
        // Get the user
        $user = $tokenRecord->tokenable;
        if (!$user) {
            Log::warning('Token without associated user', [
                'ip' => $request->ip(),
                'token_id' => $tokenId,
                'user_agent' => $request->userAgent()
            ]);
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Check if user is archived
        if (method_exists($user, 'isArchived') && $user->isArchived()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Set the authenticated user
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        auth()->setUser($user);
        
        // Update last used timestamp and IP info
        $tokenRecord->update([
            'last_used_at' => now(),
            'last_used_from_ip' => $request->ip(),
            'last_used_user_agent' => $request->userAgent(),
        ]);
        
        return $next($request);
    }
}

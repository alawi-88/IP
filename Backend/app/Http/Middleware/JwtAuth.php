<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class JwtAuth
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Rate limiting for token validation attempts
        $key = 'jwt-validation:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            Log::warning('Too many JWT validation attempts', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'message' => 'Too many authentication attempts. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }
        
        RateLimiter::hit($key, 60); // 30 attempts per minute
        
        // Verify JWT token
        $payload = $this->jwtService->verifyToken($token);
        
        if (!$payload) {
            Log::warning('Invalid JWT token', [
                'ip' => $request->ip(),
                'token_preview' => substr($token, 0, 20) . '...',
                'user_agent' => $request->userAgent()
            ]);
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Get user from token
        $user = $this->jwtService->getUserFromToken($token);
        
        if (!$user) {
            Log::warning('JWT token without associated user', [
                'ip' => $request->ip(),
                'user_id' => $payload['sub'] ?? 'unknown',
                'user_type' => $payload['user_type'] ?? 'unknown',
                'user_agent' => $request->userAgent()
            ]);
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Check if user is archived
        if (method_exists($user, 'isArchived') && $user->isArchived()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check mentor-specific status validations
        if ($user instanceof \App\Models\Mentor) {
            // Check if mentor is rejected
            if ($user->status === 'rejected') {
                return response()->json(['message' => __('mentor.account_rejected')], 401);
            }

            // Check if mentor is visible
            if (!$user->is_visible) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            // Check if mentor is approved
            if ($user->status !== 'approved') {
                return response()->json(['message' => __('mentor.account_not_approved')], 401);
            }
        }
        
        // Set the authenticated user
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        auth()->setUser($user);
        
        // Add JWT payload to request for easy access
        $request->attributes->set('jwt_payload', $payload);
        
        return $next($request);
    }
}

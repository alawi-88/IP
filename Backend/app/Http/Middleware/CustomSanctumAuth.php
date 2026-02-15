<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CustomSanctumAuth
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

        // Extract the actual token part (after the |)
        $tokenParts = explode('|', $token);
        $actualToken = count($tokenParts) > 1 ? $tokenParts[1] : $token;

        // Try to find token by plain text match (since tokens are stored as plain text)
        $tokenRecord = PersonalAccessToken::where('token', $actualToken)->first();

        // If not found, try to find by the full token (in case it's stored with ID)
        if (!$tokenRecord) {
            $tokenRecord = PersonalAccessToken::where('token', $token)->first();
        }

        // If still not found, try to find by token ID (extract ID from token)
        if (!$tokenRecord && count($tokenParts) > 1) {
            $tokenId = $tokenParts[0];
            $tokenRecord = PersonalAccessToken::find($tokenId);
        }

        if (!$tokenRecord) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check if token is expired
        if ($tokenRecord->expires_at && $tokenRecord->expires_at < now()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Set the authenticated user
        $user = $tokenRecord->tokenable;
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Set the user in the request and auth
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Also set the user in the auth guard
        auth()->setUser($user);

        // Update last used timestamp
        $tokenRecord->update(['last_used_at' => now()]);

        return $next($request);
    }
}

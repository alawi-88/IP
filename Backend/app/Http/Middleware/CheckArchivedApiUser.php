<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckArchivedApiUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check all authentication guards for archived users
        $guards = ['sanctum', 'web', 'participants', 'judges', 'supervisors'];
        
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // Check if user is archived
                if (method_exists($user, 'isArchived') && $user->isArchived()) {
                    // For Sanctum users, revoke all tokens
                    if (method_exists($user, 'tokens')) {
                        $user->tokens()->delete();
                    }
                    
                    // For session-based users, logout from all guards
                    foreach ($guards as $logoutGuard) {
                        Auth::guard($logoutGuard)->logout();
                    }
                    
                    // Invalidate session if it exists
                    if ($request->hasSession()) {
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                    }
                    
                    // Return 401 Unauthorized response
                    return response()->json([
                        'message' => __('auth.archived_account'),
                        'error' => 'account_archived'
                    ], 401);
                }
            }
        }

        return $next($request);
    }
}

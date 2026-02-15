<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckArchivedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check all authentication guards for archived users
        $guards = ['web', 'participants', 'judges', 'supervisors'];
        
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // Check if user is archived (works for User, Participant, Judge models)
                if (method_exists($user, 'isArchived') && $user->isArchived()) {
                    // Log out the user from all guards
                    foreach ($guards as $logoutGuard) {
                        Auth::guard($logoutGuard)->logout();
                    }
                    
                    // Invalidate the session
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    // For API requests, return JSON response
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => __('auth.archived_account'),
                            'error' => 'account_archived'
                        ], 401);
                    }
                    
                    // For web requests, redirect to login with error message
                    return redirect()->route('login')->withErrors([
                        'email' => __('auth.archived_account'),
                    ]);
                }
            }
        }

        return $next($request);
    }
}

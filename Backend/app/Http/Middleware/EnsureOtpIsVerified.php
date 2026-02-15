<?php

namespace App\Http\Middleware;

use Closure;


class EnsureOtpIsVerified
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        if (session('admin.otp_verified') === true) {
            return $next($request);
        }

        return redirect()->route('admin.verify-otp');
    }



}

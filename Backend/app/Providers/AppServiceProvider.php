<?php

namespace App\Providers;

use App\Listeners\UpdateLastLoginAt;
use App\Models\User;
use App\Providers\ArchivedUserProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Models\ActivityLog;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Route;
use App\Models\MentorSession;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom user provider for archived users
        Auth::provider('archived_users', function ($app, array $config) {
            return new ArchivedUserProvider($app['hash'], $config['model']);
        });

        // Register MentorAuthenticationService
        $this->app->bind(\App\Services\MentorAuthenticationService::class, function ($app) {
            return new \App\Services\MentorAuthenticationService();
        });

        // Register MentorPasswordService
        $this->app->bind(\App\Services\MentorPasswordService::class, function ($app) {
            return new \App\Services\MentorPasswordService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email'));
        });
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        }); 
        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
        // RateLimiter::for('auth', function (Request $request) {
        //     return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email'));
        // });
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($request->input('email')),
            ];
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(1000)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip().'|'.$request->input('email'));
        });

        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });          

        if (!$this->app->environment('local') && !$this->app->environment('staging')) {
            // URL::forceScheme('https'); // Disabled for HTTP deployment
        }

        Password::defaults(function () {
            return Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        Event::Listen(Login::class, UpdateLastLoginAt::class);

        Gate::before(function (User $user, string $ability) {
            // Deny access if user is archived
            if ($user->isArchived()) {
                return false;
            }
            
            return $user->isSuperAdmin() ? true : null;
        });

        ActivityLog::saving(function (Activity $activity) {
            if (Auth::check()) {
                $activity->causer_id = Auth::id();
                $activity->causer_type = \App\Models\User::class;
            }
        });

        // Explicit route model binding for MentorSession
        Route::bind('session', function ($value) {
            $session = MentorSession::find($value);
            
            if (!$session) {
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => __('sessions.session_not_found'),
                    ], 404)
                );
            }
            
            return $session;
        });
        
    }
}

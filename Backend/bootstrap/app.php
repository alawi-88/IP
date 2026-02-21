<?php

use App\Http\Middleware\ApprovedProgramApplication;
use App\Http\Middleware\ApprovedProgramTab;
use App\Http\Middleware\CheckArchivedUser;
use App\Http\Middleware\CheckArchivedApiUser;
use App\Http\Middleware\CustomSanctumAuth;
use App\Http\Middleware\SecureSanctumAuth;
use App\Http\Middleware\CompatibleSanctumAuth;
use App\Http\Middleware\StrictAuthorization;
use App\Http\Middleware\JwtAuth;
use App\Http\Middleware\LanguageSwitcher;
use App\Http\Middleware\ValidateUserType;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies('*');
        $middleware->alias(
            [
                'approved_program_application' => ApprovedProgramApplication::class,
                'approved_program_tab' => ApprovedProgramTab::class,
                'check.archived.user' => CheckArchivedUser::class,
                'check.archived.api.user' => CheckArchivedApiUser::class,
                'custom.sanctum' => CustomSanctumAuth::class,
                'secure.sanctum' => SecureSanctumAuth::class,
                'compatible.sanctum' => CompatibleSanctumAuth::class,
                'strict.authorization' => StrictAuthorization::class,
                'jwt.auth' => JwtAuth::class,
                'validate.user.type' => ValidateUserType::class,
            ]
        );
        $middleware->append(LanguageSwitcher::class);
        
        // Apply web-specific middleware to web routes
        $middleware->web(append: [
            CheckArchivedUser::class,
        ]);
        
        // Apply API-specific middleware to API routes
        $middleware->api(append: [
            CheckArchivedApiUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
        
        // Prevent information disclosure in API responses
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Only sanitize API responses, not web/admin routes
            if ($request->is('api/*')) {
                // Log the full error details server-side
                \Log::error('API Error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
                
                // Return generic error message without exposing internal details
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                // Handle validation exceptions differently
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $errors = $e->errors();
                    // Pick the first validation message (if any) to surface as the main message
                    $flattened = collect($errors)->flatten();
                    $firstMessage = $flattened->first() ?? 'Validation failed. / فشل التحقق من صحة البيانات.';

                    return response()->json([
                        'message' => $firstMessage,
                        'errors' => $errors,
                    ], 422);
                }
                
                // Handle database exceptions
                if ($e instanceof \Illuminate\Database\QueryException || 
                    $e instanceof \PDOException) {
                    return response()->json([
                        'message' => 'An error occurred while processing your request. Please try again or contact support if the problem persists. / حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى أو الاتصال بالدعم إذا استمرت المشكلة.',
                    ], 500);
                }
                
                // Handle HTTP exceptions
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $statusCode = $e->getStatusCode();
                    $message = $e->getMessage();
                    
                    // Don't expose internal error messages
                    if ($statusCode >= 500) {
                        $message = 'An internal server error occurred. Please try again later. / حدث خطأ في الخادم. يرجى المحاولة مرة أخرى لاحقاً.';
                    }
                    
                    return response()->json([
                        'message' => $message,
                    ], $statusCode);
                }
                
                // Generic error response for all other exceptions
                // Never expose file paths, SQL queries, or stack traces
                return response()->json([
                    'message' => 'An error occurred while processing your request. Please try again or contact support if the problem persists. / حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى أو الاتصال بالدعم إذا استمرت المشكلة.',
                ], $statusCode >= 400 && $statusCode < 600 ? $statusCode : 500);
            }
        });
    })
    ->create();

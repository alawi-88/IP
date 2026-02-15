<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $expectedType): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'No user found'
            ], 401);
        }

        // Check if the user type matches the expected type
        $userType = $this->getUserType($user);

        if ($userType !== $expectedType) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => "Access denied. Expected {$expectedType} but got {$userType}",
                'expected_type' => $expectedType,
                'actual_type' => $userType
            ], 401);
        }

        return $next($request);
    }

    /**
     * Determine the user type based on the model class
     */
    private function getUserType($user): string
    {
        $className = get_class($user);

        // Extract the model name from the full class name
        $modelName = class_basename($className);

        // Convert to lowercase for comparison
        return strtolower($modelName);
    }
}
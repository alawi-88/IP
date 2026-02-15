<?php

namespace App\Http\Middleware;

use App\Enums\CompetitionApplicationStatus;
use App\Models\CompetitionApplication;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApprovedCompetitionApplication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Exclude cancel and reschedule endpoints - they don't need application_id since session ownership is verified in controller
        $routeName = $request->route()?->getName();
        $isCancelRoute = $routeName === 'mentor-sessions.cancel';
        $isRescheduleRoute = $routeName === 'mentor-sessions.reschedule';
        
        // Only require application_id for POST, PUT, PATCH, DELETE requests (except cancel and reschedule)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']) && !$isCancelRoute && !$isRescheduleRoute) {
            request()->validate([
                'application_id' => 'required|exists:competition_applications,id',
            ]);
        }

        $applicationId = request('application_id');

        // Only check approval status if application_id is provided
        if ($applicationId) {
            $application = CompetitionApplication::find($applicationId);
            
            if (!$application) {
                return response()->json(['message' => 'Application not found'], 404);
            }

            // IDOR Prevention: Verify that the application belongs to the authenticated user
            $userId = auth()->id();
            if ($userId && $application->participant_id !== $userId) {
                return response()->json(['message' => 'Application not found'], 404);
            }

            if ($application->status !== CompetitionApplicationStatus::Approved->value) {
                return response()->json(['message' => 'Must have all applications approved'], 403);
            }
        }

        return $next($request);
    }
}

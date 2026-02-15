<?php

namespace App\Http\Middleware;

use App\Models\CompetitionApplication;
use App\Models\CompetitionTab;
use App\Models\MentorSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApprovedCompetitionTab
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tabEndpointMap = [
            'teams.index' => 'teams',
            'teams.show' => 'teams',
            'teams.store' => 'teams',
            'teams.update' => 'teams',
            'teams.members.update' => 'teams',
            'teams.members.delete' => 'teams',
            'teams.mark-as-completed' => 'teams',

            'my-team.show' => 'my-team',

            'events.index' => 'events',
            'events.show' => 'events',

            'mentors.index' => 'mentors',
            'mentors.show' => 'mentors',

            'mentor-sessions.index' => 'mentors',
            'mentor-sessions.show' => 'mentors',
            'mentor-sessions.store' => 'mentors',
            'mentor-sessions.cancel' => 'mentors',
            'mentors.available-slots' => 'mentors',
            'mentors.mentor-sessions.index' => 'mentors',
            'mentors.mentor-sessions.store' => 'mentors',

            'guidelines.index' => 'guidelines',
            'guidelines.show' => 'guidelines',

            'projects.index' => 'projects',
            'projects.show' => 'projects',
            'projects.store' => 'projects',
            'projects.is-submitted' => 'projects',

            'winners.index' => 'winners',
        ];

        $routeName = $request->route()->getName();
        
        // If route name is not in the map, skip tab visibility check
        if (!isset($tabEndpointMap[$routeName])) {
            return $next($request);
        }

        // Special handling for cancel route - get competition_id from session
        if ($routeName === 'mentor-sessions.cancel') {
            $sessionId = $request->route('session');
            if ($sessionId instanceof MentorSession) {
                $competitionId = $sessionId->competition_id;
            } else {
                $session = MentorSession::find($sessionId);
                if (!$session) {
                    // Let the controller handle the 404
                    return $next($request);
                }
                $competitionId = $session->competition_id;
            }
        } else {
            // For other routes, use application_id
            $applicationId = request('application_id');
            
            // Skip if no application_id is provided (might be optional for some routes)
            if (!$applicationId) {
                return $next($request);
            }
            
            $competitionId = CompetitionApplication::findOrFail($applicationId)->competition_id;
        }

        $isCompetitionTabVisible = $this->getCompetitionTabVisibility($competitionId, $tabEndpointMap[$routeName]);

        if (!$isCompetitionTabVisible) {
            return response()->json(['message' => 'Tab not approved'], 403);
        }

        return $next($request);
    }

    private function getCompetitionTabVisibility($competitionId, $tabEndpoint): bool
    {
        return CompetitionTab::where('competition_id', $competitionId)
            ->where('tab', $tabEndpoint)
            ->where('is_visible', true)
            ->exists();
    }
}

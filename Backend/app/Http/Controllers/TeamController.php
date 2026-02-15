<?php

namespace App\Http\Controllers;

use App\Filters\Teams\SubTracks;
use App\Filters\Teams\Tracks;
use App\Http\Requests\DeleteTeamMemberRequest;
use App\Http\Requests\TeamRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Services\Team;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use App\Models\Team as TeamModel;
class TeamController extends Controller
{
    public function __construct(protected readonly Team $team)
    {
    }

    public function index(): ResourceCollection
    {
        // Get application_id from request
        $applicationId = request('application_id');
        
        if (!$applicationId) {
            return TeamResource::collection(collect());
        }
        
        // Get competition_id from the application
        $application = \App\Models\CompetitionApplication::findOrFail($applicationId);
        
        // IDOR Prevention: Verify that the application belongs to the authenticated user
        $userId = auth()->id();
        if ($userId && $application->participant_id !== $userId) {
            abort(404, 'Application not found');
        }
        
        $competitionId = $application->competition_id;
        
        // Filter teams by competition_id through the application relationship
        $teamsQuery = TeamModel::published()
            ->whereHas('application', function ($query) use ($competitionId) {
                $query->where('competition_id', $competitionId);
            })
            ->active(); // Only show non-archived teams

        // Use the correct filter classes, not Eloquent models, in the pipeline
        $filteredTeams = app(Pipeline::class)
            ->send($teamsQuery)
            ->through([
                Tracks::class,
                SubTracks::class,
            ])
            ->thenReturn();

        return TeamResource::collection($filteredTeams->get());
    }

    public function store(TeamRequest $request): JsonResource
    {
        $team = $this->team->store($request->validated('application_id'), $request->validated());

        return new TeamResource($team->fresh());
    }

    public function show(TeamModel $team): JsonResource|JsonResponse
    {
        // Prevent participants from viewing archived teams
        if ($team->isArchived()) {
            //abort(404, 'Team not found');
            return response()->json(['message' => __('team_archive.team_not_found')], 404);
        }

        $team = $this->team->show($team->id);

        if (!$team) {
            //abort(404, 'Team not found');
            return response()->json(['message' => __('team_archive.team_not_found')], 404);
        }

        return new TeamResource($team);
    }

    public function update(TeamModel $team, UpdateTeamRequest $request): JsonResource
    {
        $this->isAllowedToUpdate($team);

        $team = $this->team->update($team, $request->validated());

        return new TeamResource($team->fresh());
    }

    public function markAsCompleted(TeamModel $team): JsonResource
    {
        $this->isAllowedToUpdate($team);

        $team->update(['is_completed' => true]);

        return new TeamResource($team->fresh());
    }

    public function updateTeamMembers(TeamModel $team, UpdateTeamMemberRequest $request): JsonResource
    {
        $this->isAllowedToUpdate($team);
        $team = $this->team->updateTeamMembers($team, $request->validated('serial_numbers'));

        return new TeamResource($team->fresh());
    }

    // delete team members
    public function deleteTeamMembers(TeamModel $team, DeleteTeamMemberRequest $request): JsonResource|JsonResponse
    {
        try {
            $this->isAllowedToUpdate($team);

            $serialNumbers = $request->validated('serial_numbers');
            
            if (empty($serialNumbers)) {
                return response()->json([
                    'message' => 'No serial numbers provided for deletion.',
                ], 422);
            }

            $team = $this->team->deleteTeamMembers($team, $serialNumbers);

            return new TeamResource($team->fresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error deleting team members', [
                'team_id' => $team->id,
                'user_id' => auth()->id(),
                'serial_numbers' => $request->input('serial_numbers'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while deleting team members. Please try again.',
            ], 500);
        }
    }

    /**
     * @param TeamModel $team
     * @return void
     */
    public function isAllowedToUpdate(TeamModel $team): void
    {
        $leader = $team->members->firstWhere('participant_id', auth()->id());

        if ($team->is_published) {
            if (!$leader) {
                abort(403, 'You are not a member of this team');
            }

            if (!$leader->is_leader) {
                abort(403, 'You cannot update a published team');
            }
        }

        if ($team->application->competition->currentStage()?->slug != 'team-formation') {
            abort(403, 'You cannot update a team in the team formation stage');
        }
    }
}

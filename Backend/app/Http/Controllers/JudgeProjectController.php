<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Request;

class JudgeProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResourceCollection
    {
        // Check if judge is archived
        $judge = auth('judges')->user();
        if (!$judge) {
            abort(401, 'Unauthorized');
        }

        if (method_exists($judge, 'isArchived') && $judge->isArchived()) {
            abort(401, 'Account has been archived');
        }

        $type = $request->query('type');
        $competitionId = $request->query('competition_id');
        $stageId = $request->query('stage_id');

        $projectsQuery = $judge->projects()
            ->active()
            // Eager load only real Eloquent relations to avoid calling `with` on a collection
            ->with(['team.application', 'application', 'form.stagesByFormId']);

        if ($type === 'team') {
            $projectsQuery->whereHas('team.application', function ($q) {
                $q->where('registered_as_team', 1);
            });
        } elseif ($type === 'individual') {
            $projectsQuery->whereHas('team.application', function ($q) {
                $q->where('registered_as_team', 0)->orWhereNull('registered_as_team');
            });
        }

        if ($competitionId) {
            $projectsQuery->whereHas('application', function ($q) use ($competitionId) {
                $q->where('competition_id', $competitionId);
            });
        }

        if ($stageId) {
            $projectsQuery->whereHas('form.stages', function ($q) use ($stageId) {
                $q->where('id', $stageId);
            });
        }

        $projects = $projectsQuery->get();

        return ProjectResource::collection($projects);
    }


    /**
     * Display the specified resource.
     */
    public function show($projectId): ProjectResource
    {
        // Check if judge is archived
        $judge = auth('judges')->user();
        if (!$judge) {
            abort(401, 'Unauthorized');
        }

        if (method_exists($judge, 'isArchived') && $judge->isArchived()) {
            abort(401, 'Account has been archived');
        }

        $project = $judge->projects()->active()->findOrFail($projectId);

        return new ProjectResource($project);
    }
}

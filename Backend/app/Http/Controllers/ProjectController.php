<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\Project;
use App\Models\Project as ProjectModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectController extends Controller
{
    public function __construct(private readonly Project $service)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResourceCollection
    {
        return ProjectResource::collection($this->service->list($request));
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store or update a draft project for the current team, and allow final submission.
     *
     * This method allows a team to create a draft project, update it multiple times,
     * and finally submit it. If a draft exists for the team, it will be updated.
     * If a final project has already been submitted, further changes are not allowed.
     * The request should include a 'status' field (e.g., 'draft' or 'submitted').
     */
    public function store(ProjectRequest $request): JsonResource|JsonResponse
    {
//        if ($this->service->existsForTeam(myTeam())) {
//            return response()->json(['message' => 'Project already exists for this team'], 400);
//        }

        try {
            $project = $this->service->store($request->validated());
            return new ProjectResource($project->fresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to let Laravel handle them properly
            throw $e;
        } catch (\Exception $e) {
            // Log the full error details server-side
            \Log::error('Project submission error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            // Return generic error message without exposing internal details
            return response()->json([
                'message' => 'An error occurred while saving your project. Please try again or contact support if the problem persists. / حدث خطأ أثناء حفظ مشروعك. يرجى المحاولة مرة أخرى أو الاتصال بالدعم إذا استمرت المشكلة.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectModel $project): JsonResource
    {
        // Prevent participants from viewing archived projects
        if ($project->isArchived()) {
            abort(404, 'Project not found');
        }

        return new ProjectResource($project);
    }

    public function isSubmitted(): JsonResponse
    {
        $isSubmitted = $this->service->existsForTeam(myTeam());

        return response()->json(['is_submitted' => $isSubmitted]);
    }

    /**
     * Reset/Delete a draft project.
     * This removes the draft completely, allowing the user to start fresh.
     */
    public function resetDraft(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
            ]);

            $projectId = $request->input('project_id');

            // Find the draft project
            $draft = ProjectModel::where('id', $projectId)
                ->where('type', 'draft')
                ->where('is_archived', false)
                ->first();

            if (!$draft) {
                return response()->json([
                    'message' => __('project_submitted.draft_not_found', [], 'en'),
                ], 404);
            }

            // IDOR Prevention: Verify that the project belongs to the authenticated user
            $userId = auth()->id();
            $hasAccess = false;
            
            // Check if project belongs to user through application
            if ($draft->application_id) {
                $application = \App\Models\CompetitionApplication::find($draft->application_id);
                if ($application && $application->participant_id === $userId) {
                    $hasAccess = true;
                }
            }
            
            // Check if project belongs to user through team membership
            if (!$hasAccess && $draft->team_id) {
                $team = \App\Models\Team::find($draft->team_id);
                if ($team && $team->members()->where('participant_id', $userId)->exists()) {
                    $hasAccess = true;
                }
            }
            
            if (!$hasAccess) {
                return response()->json([
                    'message' => __('project_submitted.draft_not_found', [], 'en'),
                ], 404);
            }

            // Only allow deletion of drafts, not submissions
            if ($draft->type !== 'draft') {
                return response()->json([
                    'message' => __('project_submitted.cannot_delete_submission', [], 'en'),
                ], 403);
            }

            // Delete the draft
            $draft->delete();

            return response()->json([
                'message' => __('project_submitted.draft_reset_success', [], 'en'),
                'success' => true,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error resetting draft project', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'An error occurred while resetting the draft. Please try again or contact support if the problem persists. / حدث خطأ أثناء إعادة تعيين المسودة. يرجى المحاولة مرة أخرى أو الاتصال بالدعم إذا استمرت المشكلة.',
            ], 500);
        }
    }
}

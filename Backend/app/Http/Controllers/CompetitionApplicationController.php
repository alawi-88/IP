<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompetitionApplicationRequest;
use App\Http\Resources\CompetitionApplicationResource;
use App\Jobs\ProcessCompetitionApplicationAiEvaluation;
use App\Models\FormAiScoringConfig;
use App\Models\CompetitionApplication;
use App\Notifications\CompetitionRegistration;
use App\Services\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Services\CompetitionApplication as CompetitionApplicationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Competition;
class CompetitionApplicationController extends Controller
{
    public function __construct(
        private readonly CompetitionApplicationService $applicationService,
        private readonly Team $teamService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
{
    $programTypeSlug = request()->query('program_type');
    $participantId   = auth()->id();

    if ($programTypeSlug) {
        $programType = Str::headline(str_replace('-', ' ', $programTypeSlug));

        $applications = CompetitionApplication::where('participant_id', $participantId)
            ->whereHas('competition', function ($q) use ($programType) {
                $q->where('type', $programType)
                  ->where('is_archived', false);
            })
            ->submission()
            ->active()
            ->get();

        return response()->json([
            'data' => CompetitionApplicationResource::collection($applications),
            'programs_type' => $this->getProgramsCountByType(),
        ]);
    }

    return response()->json([
        'data' => CompetitionApplicationResource::collection(
            $this->applicationService->getMyApplications()
        ),
        'programs_type' => $this->getProgramsCountByType(),
    ]);
}

    public function getProgramsCountByType(): array
    {
        $types = ['Hackathon', 'Sandbox', 'Idea Bank'];

        // join applications with competitions to fetch competition type
        $counts = CompetitionApplication::query()
            ->join('competitions', 'competition_applications.competition_id', '=', 'competitions.id')
            ->whereIn('competitions.type', $types)
            ->where('competition_applications.is_archived', false)
            ->where('competitions.is_archived', false)
            ->selectRaw('competitions.type, COUNT(*) as total')
            ->groupBy('competitions.type')
            ->pluck('total', 'competitions.type')
            ->toArray();

        $programsCountByType = [];
        foreach ($types as $type) {
            $snakeKey = Str::snake($type, '_');
            $count = $counts[$type] ?? 0;

            $programsCountByType[] = [
                'title' => __('program_types.' . $snakeKey),
                'slug'  => $snakeKey,
                'count' => $count,
            ];
        }

        return $programsCountByType;
    }

public function store(CompetitionApplicationRequest $request)
    {
        try {
            $validated = $request->validated();

            $participantId = auth()->id();

            $answers = $request->input('answers');

        foreach (($request->all()['answers'] ?? []) as $key => $value) {
            // Always check for file upload for this answer key
            if ($request->hasFile("answers.$key")) {
                $file = $request->file("answers.$key");
                if ($file && $file->isValid()) {
                    $path = $file->store('uploads/files', 'public');
                    $answers[$key] = $path;
                } else {
                    $answers[$key] = null;
                }
            } elseif (is_object($value)) {
                // If the value is an object (e.g., [object Object]), set to null to avoid saving as string
                $answers[$key] = null;
            } elseif (is_string($value)) {
                // Remove full URL prefix if present (e.g., https://dev.innovation-platform.net/storage/)
                $value = preg_replace('#^https?://[^/]+/storage/#', '', $value);
                // If the value is a string and looks like a file URL, keep as is (assume it's already uploaded)
                if (preg_match('#^uploads/files/#', $value)) {
                    $answers[$key] = $value;
                } else {
                    // For non-file, non-object values, keep as is
                    $answers[$key] = $value;
                }
            } else {
                // For non-file, non-object values, keep as is
                $answers[$key] = $value;
            }
        }

        if ($request->hasFile('answers.team_logo')) {
            $path = $request->file('answers.team_logo')->store('uploads/files', 'public');
            $answers['team_logo'] = $path;
        } elseif (!empty($answers['team_logo']) && is_string($answers['team_logo'])) {
            // Remove both with and without the domain prefix
            $answers['team_logo'] = preg_replace([
                '#^https?://dev\.innovation-platform\.net/storage/#',
                '#^/storage/#'
            ], '', $answers['team_logo']);
        }

        $alreadyCreated = false;
        if (!empty($validated['form_id'])) {
            $alreadyExists = CompetitionApplication::where('form_id', $validated['form_id'])
                ->where('participant_id', $participantId)
                ->where('is_archived', false)
                ->first();

            if ($alreadyExists && $alreadyExists->type === 'submission') {
                return response()->json([
                    'message' => __('competition_application.already_submitted')
                ], 409);
            }elseif ($alreadyExists && $alreadyExists->type === 'draft') {
                $alreadyCreated = true;
                //$alreadyExists->delete();

            }
        }

        $applicationData = Arr::only($validated, [
            'competition_id',
            'form_id',
            'form_submissions',
            'status',
            'participant_id',
            'registered_as',
            'has_team',
            'team_name',
            'team_logo',
            'team_serial',
            'type',
        ]);

        if (!empty($validated['form_id'])) {
            $applicationData['form_id'] = $validated['form_id'];

            // Include team-related fields in form_submissions for draft saving
            $formSubmissions = $answers;
            $formSubmissions['team_name'] = $validated['answers']['team_name'] ?? null;
            // Keep the processed team_logo from $answers (which includes file path if uploaded)
            $formSubmissions['team_logo'] = $answers['team_logo'] ?? $validated['answers']['team_logo'] ?? null;
            $formSubmissions['team_serial'] = $validated['answers']['team_serial'] ?? null;
            $formSubmissions['register_as'] = $validated['answers']['register_as'] ?? null;
            $formSubmissions['has_team'] = $validated['answers']['has_team'] ?? null;
            $formSubmissions['track'] = $answers['track'] ?? null;
            $formSubmissions['sub_track'] = $answers['sub_track'] ?? null;

            $applicationData['form_submissions'] = $formSubmissions;
            $applicationData['registered_as'] = $validated['answers']['register_as'] ?? null;
            $applicationData['has_team'] = $validated['answers']['has_team'];
            $applicationData['team_name'] = $validated['answers']['team_name'] ?? null;
            // Keep the processed team_logo from $answers (which includes file path if uploaded)
            $applicationData['team_logo'] = $answers['team_logo'] ?? $validated['answers']['team_logo'] ?? null;
            $applicationData['team_serial'] = $validated['answers']['team_serial'] ?? null;
            $applicationData['type'] = $validated['type'] ?? 'submission';
        }

        $application = null;
        $is_new = false;

        DB::beginTransaction();
        try {
            if ($alreadyCreated) {
                // Merge new form_submissions with existing ones to preserve data from other steps
                $existingFormSubmissions = $alreadyExists->form_submissions ? $alreadyExists->form_submissions->toArray() : [];
                $mergedFormSubmissions = array_merge($existingFormSubmissions, $applicationData['form_submissions'] ?? []);
                $applicationData['form_submissions'] = $mergedFormSubmissions;

                $alreadyExists->update($applicationData);
                $application = $alreadyExists->fresh(); // Get the updated model instance
            }else{
                $application = CompetitionApplication::create($applicationData);
                $is_new = true;
            }
            //$application = CompetitionApplication::create($applicationData);

            $validated['track_id'] = $answers['track'] ?? null;
            $validated['sub_track_id'] = $answers['sub_track'] ?? null;

            // When updating an application, remove the old team (if any) and add the new team data.
            if($validated['type'] === 'submission'){
            if (isset($answers['has_team']) && $answers['has_team']) {
                // Remove old team(s) associated with this application
                //$application->team()->delete();

                // Add new team with updated data
                $this->teamService->store(
                    $application->id,
                    $this->teamService->formatTeamData($validated)
                );
            }
            }

            DB::commit();
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            // Re-throw to ensure the request fails and no partial data is saved
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if (env('APP_ENV') != 'local' && $application->type === 'submission'){
            $user = auth()->user();
            if ($user) {
                $user->notify(new CompetitionRegistration($application));
            }
        }

        // Trigger AI evaluation automatically for submitted registrations when configured
        if ($application->type === 'submission') {
            $hasAiConfig = FormAiScoringConfig::where('form_id', $application->form_id)->exists();

            if ($hasAiConfig) {
                $application->applyAiEvaluationResponse(
                    ['message' => 'AI evaluation is being processed.'],
                    'pending'
                );

                ProcessCompetitionApplicationAiEvaluation::dispatch($application->id);
            } else {
                $application->applyAiEvaluationResponse(
                    ['message' => 'AI evaluation is not configured for this form.'],
                    'skipped'
                );
            }
        }

        return new CompetitionApplicationResource($application->fresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to let Laravel handle them properly
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            // Return generic error message without exposing SQL or file paths
            return response()->json([
                'message' => 'An error occurred while saving your application. Please try again or contact support if the problem persists. / حدث خطأ أثناء حفظ طلبك. يرجى المحاولة مرة أخرى أو الاتصال بالدعم إذا استمرت المشكلة.',
            ], 500);
        } catch (\Exception $e) {
            // Return generic error message without exposing internal details
            return response()->json([
                'message' => 'An error occurred while saving your application. Please try again or contact support if the problem persists. / حدث خطأ أثناء حفظ طلبك. يرجى المحاولة مرة أخرى أو الاتصال بالدعم إذا استمرت المشكلة.',
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(CompetitionApplication $competitionApplication): JsonResource
    {
        return new CompetitionApplicationResource($this->applicationService->show($competitionApplication));
    }

    /**
     * Reset/Delete a draft application.
     * This removes the draft completely, allowing the user to start fresh.
     */
    public function resetDraft(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'application_id' => 'required|integer|exists:competition_applications,id',
            ]);

            $participantId = auth()->id();
            $applicationId = $request->input('application_id');

            // Find the draft application
            $draft = CompetitionApplication::where('id', $applicationId)
                ->where('participant_id', $participantId)
                ->where('type', 'draft')
                ->where('is_archived', false)
                ->first();

            if (!$draft) {
                return response()->json([
                    'message' => __('competition_application.draft_not_found', [], 'en'),
                ], 404);
            }

            // Only allow deletion of drafts, not submissions
            if ($draft->type !== 'draft') {
                return response()->json([
                    'message' => __('competition_application.cannot_delete_submission', [], 'en'),
                ], 403);
            }

            // Delete the draft
            $draft->delete();

            return response()->json([
                'message' => __('competition_application.draft_reset_success', [], 'en'),
                'success' => true,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while resetting the draft. Please try again or contact support if the problem persists. / حدث خطأ أثناء إعادة تعيين المسودة. يرجى المحاولة مرة أخرى أو الاتصال بالدعم إذا استمرت المشكلة.',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Filters\Mentors\Search;
use App\Filters\Mentors\HasAvailability;
use App\Filters\Mentors\Profession;
use App\Http\Resources\MentorResource;
use App\Models\Mentor;
use App\Models\CompetitionApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pipeline\Pipeline;

class MentorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): ResourceCollection|JsonResponse
    {
        // Get application_id from request
        $applicationId = request('application_id');

        if (!$applicationId) {
            return MentorResource::collection(collect());
        }

        // Get application with relations
        $application = CompetitionApplication::with(['participant.mentors', 'team.mentors'])->findOrFail($applicationId);
        
        // IDOR Prevention: Verify that the application belongs to the authenticated user
        $userId = auth()->id();
        if ($userId && $application->participant_id !== $userId) {
            abort(404, 'Application not found');
        }
        
        $competitionId = $application->competition_id;

        // Collect mentor IDs assigned directly to the participant or to their team
        $assignedMentorIds = collect();

        if ($application->participant) {
            $assignedMentorIds = $assignedMentorIds->merge(
                $application->participant
                    ->mentors()
                    ->pluck('mentors.id')
                    ->toArray()
            );
        }

        if ($application->team) {
            $assignedMentorIds = $assignedMentorIds->merge(
                $application->team
                    ->mentors()
                    ->pluck('mentors.id')
                    ->toArray()
            );
        }

        $assignedMentorIds = $assignedMentorIds->unique()->values()->all();

        // If no mentors have been assigned, return an empty collection
        if (empty($assignedMentorIds)) {
            return MentorResource::collection(collect());
        }

        // Since $application->competitions() does not exist, fall back to single competition_id logic
        $competitionIds = [$competitionId];

        $mentorsQuery = Mentor::whereIn('id', $assignedMentorIds)
            ->where('is_visible', true)
            ->where('status', 'approved')
            ->whereHas('competitions', function ($q) use ($competitionIds) {
                $q->whereIn('competitions.id', $competitionIds);
            })
            ->with(['competitions', 'track', 'competition'])
            ->active(); // Only show non-archived mentors

        if (!config('video_tools.google.use_global_account', false)) {
            $mentorsQuery->whereHas('videoTools', function ($q) {
                // Only show mentors who have at least one valid video tool
                // Valid means: is_active = true, has access_token, and token is not expired
                $q->where('is_active', true);
            });
        }

        $filteredMentors = app(Pipeline::class)
            ->send($mentorsQuery)
            ->through([
                Search::class,
                HasAvailability::class,
                Profession::class,
            ])
            ->thenReturn();

        // Check if filters are applied before pagination
        $hasFilters = request()->has('search') ||
                     request()->has('has_availability') ||
                     request()->has('profession') ||
                     request()->has('date');

        $paginatedMentors = $filteredMentors
            ->orderBy('id')
            ->cursorPaginate(config('resource.mentors_pagination_limit'));

        // Check if results are empty
        if ($paginatedMentors->isEmpty()) {
            $message = $hasFilters
                ? __('sessions.no_mentors_found')
                : __('sessions.no_mentors_available');

            return response()->json([
                'data' => [],
                'message' => $message,
                'links' => $paginatedMentors->toArray()['links'] ?? [],
                'meta' => $paginatedMentors->toArray()['meta'] ?? [
                    'path' => request()->url(),
                    'per_page' => config('resource.mentors_pagination_limit'),
                ],
            ]);
        }

        return MentorResource::collection($paginatedMentors);
    }

    /**
     * Display the specified resource.
     */
    public function show($mentor): JsonResource
    {
        // application_id is required to scope mentor to current competition
        $applicationId = request('application_id');

        if (!$applicationId) {
            abort(404, 'Mentor not found.');
        }

        $application = CompetitionApplication::with(['participant.mentors', 'team.mentors'])->find($applicationId);
        if (!$application) {
            abort(404, 'Application not found.');
        }
        
        // IDOR Prevention: Verify that the application belongs to the authenticated user
        $userId = auth()->id();
        if ($userId && $application->participant_id !== $userId) {
            abort(404, 'Application not found.');
        }
        
        $competitionId = $application->competition_id;

        // Collect mentor IDs assigned directly to the participant or to their team
        $assignedMentorIds = collect();

        if ($application->participant) {
            $assignedMentorIds = $assignedMentorIds->merge(
                $application->participant
                    ->mentors()
                    ->pluck('mentors.id')
                    ->toArray()
            );
        }

        if ($application->team) {
            $assignedMentorIds = $assignedMentorIds->merge(
                $application->team
                    ->mentors()
                    ->pluck('mentors.id')
                    ->toArray()
            );
        }

        $assignedMentorIds = $assignedMentorIds->unique()->values()->all();

        // Prevent ID tampering: the requested mentor must be assigned to this participant/team
        if (empty($assignedMentorIds) || !in_array((int) $mentor, $assignedMentorIds)) {
            abort(404, 'Mentor not found.');
        }

        // Fetch mentor manually with competition and visibility constraints
        $mentorModel = Mentor::query()
            ->where('id', $mentor)
            ->where('is_visible', true)
            ->where('status', 'approved')
            ->whereHas('competitions', function ($q) use ($competitionId) {
                $q->where('competitions.id', $competitionId);
            })
            ->active()
            ->with(['competitions', 'track', 'competition'])
            ->first();

        if (!$mentorModel) {
            abort(404, 'Mentor not found.');
        }

        return new MentorResource($mentorModel);
    }
}

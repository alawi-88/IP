<?php

namespace App\Http\Controllers\Api\Participant;

use App\Http\Controllers\Controller;
use App\Models\CompetitionApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApplicationEditController extends Controller
{
    /**
     * IN-2034: Get decision reason for an application
     */
    public function getDecisionDetails(Request $request, int $applicationId): JsonResponse
    {
        $participant = $request->user();

        $application = CompetitionApplication::where('id', $applicationId)
            ->where('participant_id', $participant->id)
            ->first();

        if (!$application) {
            return response()->json(['success' => false, 'message' => 'Application not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $application->id,
                'status' => $application->status,
                'decision_reason' => $application->decision_reason,
                'reviewed_at' => $application->reviewed_at?->toISOString(),
                'editable_fields' => $application->editable_fields,
                'edit_notes' => $application->edit_notes,
                'edit_requested_at' => $application->edit_requested_at?->toISOString(),
                'resubmitted_at' => $application->resubmitted_at?->toISOString(),
                'final_evaluation_score' => $application->final_evaluation_score,
            ],
        ]);
    }

    /**
     * IN-2035: Check if an edit has been requested
     */
    public function checkEditRequest(Request $request, int $applicationId): JsonResponse
    {
        $participant = $request->user();

        $application = CompetitionApplication::where('id', $applicationId)
            ->where('participant_id', $participant->id)
            ->first();

        if (!$application) {
            return response()->json(['success' => false, 'message' => 'Application not found'], 404);
        }

        $hasEditRequest = $application->status === 'edit_requested';

        return response()->json([
            'success' => true,
            'data' => [
                'has_edit_request' => $hasEditRequest,
                'editable_fields' => $hasEditRequest ? $application->editable_fields : null,
                'edit_notes' => $hasEditRequest ? $application->edit_notes : null,
                'edit_requested_at' => $hasEditRequest ? $application->edit_requested_at?->toISOString() : null,
            ],
        ]);
    }

    /**
     * IN-2036: Edit approved fields only and resubmit
     */
    public function submitEdit(Request $request, int $applicationId): JsonResponse
    {
        $participant = $request->user();

        $application = CompetitionApplication::where('id', $applicationId)
            ->where('participant_id', $participant->id)
            ->where('status', 'edit_requested')
            ->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found or not in edit-requested status.',
            ], 404);
        }

        $editableFields = $application->editable_fields ?? [];

        if (empty($editableFields)) {
            return response()->json([
                'success' => false,
                'message' => 'No fields are marked as editable.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'form_submissions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $submittedData = $request->input('form_submissions');
        $currentSubmissions = $application->form_submissions;

        if ($currentSubmissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes) {
            $currentArray = $currentSubmissions->toArray();
        } elseif (is_array($currentSubmissions)) {
            $currentArray = $currentSubmissions;
        } elseif (is_string($currentSubmissions)) {
            $currentArray = json_decode($currentSubmissions, true) ?? [];
        } else {
            $currentArray = [];
        }

        // Only update allowed fields
        $updatedSubmissions = $currentArray;
        $changedFields = [];

        foreach ($submittedData as $fieldSlug => $value) {
            if (in_array($fieldSlug, $editableFields)) {
                $updatedSubmissions[$fieldSlug] = $value;
                $changedFields[] = $fieldSlug;
            }
        }

        if (empty($changedFields)) {
            return response()->json([
                'success' => false,
                'message' => 'No editable fields were provided.',
            ], 422);
        }

        // Update the application
        $application->form_submissions = $updatedSubmissions;
        $application->status = 'pending'; // Reset back to pending for review
        $application->resubmitted_at = now();
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Application updated successfully. It has been resubmitted for review.',
            'data' => [
                'id' => $application->id,
                'status' => $application->status,
                'changed_fields' => $changedFields,
                'resubmitted_at' => $application->resubmitted_at->toISOString(),
            ],
        ]);
    }
}

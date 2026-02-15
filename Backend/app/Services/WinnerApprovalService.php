<?php

namespace App\Services;

use App\Events\ApprovalRequestStatusChanged;
use App\Events\ApproverAssignedToRequest;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestLevel;
use App\Models\ApprovalWorkflow;
use App\Models\Winner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WinnerApprovalService
{
    public function hasWorkflowForAction(string $actionType): bool
    {
        return ApprovalWorkflow::where('action', "Winner.{$actionType}")
            ->where('is_active', true)
            ->exists();
    }

    public function getWorkflowForAction(string $actionType): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::where('action', "Winner.{$actionType}")
            ->where('is_active', true)
            ->first();
    }

    public function createApprovalRequest(
        string $actionType,
        array $actionData,
        ?int $winnerId = null,
        ?string $reason = null,
        ?int $requestedBy = null
    ): ?ApprovalRequest {
        $requestedBy = $requestedBy ?? auth()->id();

        if (!$requestedBy) {
            $firstUser = \App\Models\User::first();
            $requestedBy = $firstUser ? $firstUser->id : null;
        }

        if (!$requestedBy) {
            Log::error('No user ID available for winner approval request creation');
            return null;
        }

        $workflow = $this->getWorkflowForAction($actionType);
        if (!$workflow) {
            return null;
        }

        // Prevent duplicates for the same winner/action/requester while pending.
        // For create, target_id is null so we skip duplicate protection.
        if ($winnerId) {
            $existingRequest = ApprovalRequest::where('target_type', Winner::class)
                ->where('target_id', $winnerId)
                ->where('action', "Winner.{$actionType}")
                ->where('status', 'pending')
                ->where('requested_by', $requestedBy)
                ->first();

            if ($existingRequest) {
                Log::warning("Duplicate approval request attempted for winner {$winnerId}, action {$actionType} by user {$requestedBy}");
                return null;
            }
        }

        try {
            DB::beginTransaction();

            $request = ApprovalRequest::create([
                'action' => "Winner.{$actionType}",
                'target_type' => Winner::class,
                'target_id' => $winnerId,
                'requested_by' => $requestedBy,
                'approval_workflow_id' => $workflow->id,
                'status' => 'pending',
                'reason' => $reason,
                'action_data' => array_merge($actionData, ['action_type' => $actionType]),
            ]);

            $this->createApprovalLevels($request, $workflow);

            DB::commit();

            event(new ApprovalRequestStatusChanged($request, 'created', 'pending'));

            // Notify approvers (in-app bell + email), same behavior as other approval services.
            $this->notifyApprovers($request);

            return $request;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create winner approval request: ' . $e->getMessage());
            return null;
        }
    }

    protected function createApprovalLevels(ApprovalRequest $request, ApprovalWorkflow $workflow): void
    {
        $approvalLevels = $workflow->approvalLevels()->orderBy('level_number')->get();

        foreach ($approvalLevels as $level) {
            ApprovalRequestLevel::create([
                'approval_request_id' => $request->id,
                'level_number' => $level->level_number,
                'role_ids' => $level->role_ids,
                'required_approvals' => $level->required_approvals,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Send notifications to approvers for a new approval request.
     */
    protected function notifyApprovers(ApprovalRequest $request): void
    {
        try {
            $approvers = $this->getApproversForRequest($request);

            $level = $request->getCurrentLevel();
            $levelNumber = (int) ($level?->level_number ?? 1);

            foreach ($approvers as $approver) {
                event(new ApproverAssignedToRequest($request, $approver, $levelNumber));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify approvers: ' . $e->getMessage());
        }
    }

    /**
     * Get approvers for a specific request from its approval levels.
     */
    protected function getApproversForRequest(ApprovalRequest $request): \Illuminate\Support\Collection
    {
        $roleIds = [];
        foreach ($request->approvalRequestLevels as $level) {
            $roleIds = array_merge($roleIds, $level->role_ids);
        }

        $roleIds = array_unique($roleIds);

        return \App\Models\User::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('id', $roleIds);
        })->get();
    }

    /**
     * Send notification to a specific approver (Filament bell + email).
     */
    protected function sendApproverNotification(\App\Models\User $approver, ApprovalRequest $request): void
    {
        $level = $request->getCurrentLevel();
        $levelNumber = (int) ($level?->level_number ?? 1);

        event(new ApproverAssignedToRequest($request, $approver, $levelNumber));
    }

    /**
     * Filament-friendly helper:
     * - If a workflow exists, create an approval request and return requires_approval=true.
     * - If not, do NOT execute anything (caller can proceed with normal save/delete).
     */
    public function processAction(
        string $actionType,
        array $actionData,
        ?int $winnerId = null,
        ?string $reason = null,
        ?int $requestedBy = null
    ): array {
        if ($this->hasWorkflowForAction($actionType)) {
            $request = $this->createApprovalRequest($actionType, $actionData, $winnerId, $reason, $requestedBy);

            if ($request) {
                return [
                    'success' => true,
                    'requires_approval' => true,
                    'request_id' => $request->id,
                    'message' => 'Request submitted for approval / تم تقديم الطلب للموافقة',
                ];
            }

            // Duplicate or failure
            $isDuplicate = $winnerId
                ? ApprovalRequest::where('target_type', Winner::class)
                    ->where('target_id', $winnerId)
                    ->where('action', "Winner.{$actionType}")
                    ->where('status', 'pending')
                    ->where('requested_by', $requestedBy ?? auth()->id())
                    ->exists()
                : false;

            return [
                'success' => false,
                'requires_approval' => false,
                'message' => $isDuplicate
                    ? 'A pending request already exists for this winner and action / يوجد طلب معلق بالفعل لهذا الفائز والإجراء'
                    : 'Failed to create approval request / فشل في إنشاء طلب الموافقة',
            ];
        }

        return [
            'success' => true,
            'requires_approval' => false,
            'message' => 'No approval workflow found; proceeding normally / لا يوجد مسار اعتماد، سيتم التنفيذ مباشرة',
        ];
    }
}



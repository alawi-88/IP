<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestLevel;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Events\ApproverAssignedToRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ApprovalRequestService
{
    /**
     * Create an approval request for an action
     */
    public function createApprovalRequest(string $action, User $requestedBy, array $actionData = [], string $reason = null): ?ApprovalRequest
    {
        // Check if there's an approval workflow for this action
        $workflow = ApprovalWorkflow::where('action', $action)
            ->where('is_active', true)
            ->with('approvalLevels')
            ->first();

        if (!$workflow) {
            // No approval required, return null
            return null;
        }

        // Create the approval request
        $approvalRequest = ApprovalRequest::create([
            'action' => $action,
            'status' => 'pending',
            'requested_by' => $requestedBy->id,
            'approval_workflow_id' => $workflow->id,
            'action_data' => $actionData,
            'reason' => $reason,
        ]);

        // Create approval request levels
        $this->createApprovalRequestLevels($approvalRequest, $workflow);

        // Notify approvers for the first level
        $this->notifyApproversForLevel($approvalRequest, 1);

        return $approvalRequest;
    }

    /**
     * Create approval request levels based on workflow
     */
    protected function createApprovalRequestLevels(ApprovalRequest $approvalRequest, ApprovalWorkflow $workflow): void
    {
        foreach ($workflow->approvalLevels as $level) {
            ApprovalRequestLevel::create([
                'approval_request_id' => $approvalRequest->id,
                'level_number' => $level->level_number,
                'status' => 'pending',
                'role_ids' => $level->role_ids,
                'required_approvals' => $level->required_approvals ?? 1,
            ]);
        }
    }

    /**
     * Notify approvers for a specific level
     */
    protected function notifyApproversForLevel(ApprovalRequest $approvalRequest, int $levelNumber): void
    {
        $workflow = $approvalRequest->approvalWorkflow;
        $level = $workflow->approvalLevels()
            ->where('level_number', $levelNumber)
            ->first();

        if (!$level) {
            return;
        }

        // Notify ALL admins who have permission to approve approval requests.
        // This matches Filament visibility checks in `ApprovalRequestResource` (auth()->user()->can('approve ApprovalRequest')).
        $approvers = User::permission('approve ApprovalRequest')
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_archived'), function ($query) {
                $query->where('is_archived', false);
            })
            ->get();

        if ($approvers->isEmpty()) {
            Log::warning("No approvers found for level {$levelNumber} of approval request {$approvalRequest->id}");
            return;
        }

        // Send notifications to approvers and trigger assignment event
        foreach ($approvers as $approver) {
            // Trigger assignment event which will be handled by the listener
            event(new ApproverAssignedToRequest($approvalRequest, $approver, $levelNumber));
        }
    }

    /**
     * Send approval notification to approver
     */
    protected function sendApprovalNotification(User $approver, ApprovalRequest $approvalRequest, int $levelNumber): void
    {
        try {
            // In a real implementation, you would send email/notification here.
            // Notification::send($approver, new ApprovalRequestNotification($approvalRequest, $levelNumber));
        } catch (\Exception $e) {
            Log::error("Failed to send approval notification to user {$approver->id}: " . $e->getMessage());
        }
    }

    /**
     * Approve a request level
     */
    public function approveLevel(ApprovalRequest $approvalRequest, int $levelNumber, User $approver, string $comment = null): array
    {
        $level = $approvalRequest->approvalRequestLevels()
            ->where('level_number', $levelNumber)
            ->where('status', 'pending')
            ->first();

        if (!$level) {
            return ['success' => false, 'message' => 'Level not found or not pending.'];
        }

        // Check if user has permission to approve this level
        $permissionCheck = $this->canUserApproveLevel($approver, $approvalRequest, $levelNumber);
        if (!$permissionCheck['can_approve']) {
            return ['success' => false, 'message' => $permissionCheck['message'] ?? 'User cannot approve this level.'];
        }

        $result = $level->approve($approver, $comment);

        // Only move to next level when this level is finalized (threshold reached)
        if (($result['success'] ?? false) && ($result['finalized'] ?? false) && ($result['status'] ?? null) === 'approved') {
            $nextLevel = $approvalRequest->getNextLevel($levelNumber);
            if ($nextLevel) {
                $this->notifyApproversForLevel($approvalRequest, $nextLevel->level_number);
            }
        }

        return $result;
    }

    /**
     * Reject a request level
     */
    public function rejectLevel(ApprovalRequest $approvalRequest, int $levelNumber, User $approver, string $comment = null): array
    {
        $level = $approvalRequest->approvalRequestLevels()
            ->where('level_number', $levelNumber)
            ->where('status', 'pending')
            ->first();

        if (!$level) {
            return ['success' => false, 'message' => 'Level not found or not pending.'];
        }

        // Check if user has permission to reject this level
        $permissionCheck = $this->canUserApproveLevel($approver, $approvalRequest, $levelNumber);
        if (!$permissionCheck['can_approve']) {
            return ['success' => false, 'message' => $permissionCheck['message'] ?? 'User cannot reject this level.'];
        }

        $result = $level->reject($approver, $comment);

        return $result;
    }

    /**
     * Check if user can approve a specific level
     * This checks both role membership and role order
     * 
     * @return array{can_approve: bool, message?: string}
     */
    public function canUserApproveLevel(User $user, ApprovalRequest $approvalRequest, int $levelNumber): array
    {
        $level = $approvalRequest->approvalRequestLevels()
            ->where('level_number', $levelNumber)
            ->first();

        if (!$level) {
            return ['can_approve' => false, 'message' => 'Level not found.'];
        }

        if (!$level->isPending()) {
            return ['can_approve' => false, 'message' => 'Level is not pending.'];
        }
        
        if ($level->hasVoted($user)) {
            return ['can_approve' => false, 'message' => 'You have already voted on this level.'];
        }
        
        $levelRoleIds = $level->role_ids ?? [];
        if (empty($levelRoleIds) || !is_array($levelRoleIds)) {
            return ['can_approve' => true];
        }
        
        $userRoleIds = $user->roles->pluck('id')->toArray();
        $matchingRoleIds = array_intersect($userRoleIds, $levelRoleIds);
        
        if (empty($matchingRoleIds)) {
            $roleNames = \Spatie\Permission\Models\Role::whereIn('id', $levelRoleIds)->pluck('name')->toArray();
            return ['can_approve' => false, 'message' => 'You do not have any of the required roles: ' . implode(', ', $roleNames)];
        }
        
        if (!$level->canUserVoteNow($user)) {
            $nextRoleId = $level->getNextRoleToVote();
            if ($nextRoleId) {
                $nextRole = \Spatie\Permission\Models\Role::find($nextRoleId);
                $nextRoleName = $nextRole ? $nextRole->name : 'Unknown';
                return ['can_approve' => false, 'message' => "It's not your turn to vote. Waiting for role: {$nextRoleName}"];
            }
            return ['can_approve' => false, 'message' => 'All required roles have already voted.'];
        }

        return ['can_approve' => true];
    }

    /**
     * Get pending approval requests for a user
     */
    public function getPendingApprovalsForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $userRoleIds = $user->roles->pluck('id')->toArray();

        return ApprovalRequest::whereHas('approvalRequestLevels', function ($query) use ($userRoleIds) {
            $query->where('status', 'pending')
                ->whereHas('approvalRequest.approvalWorkflow.approvalLevels', function ($q) use ($userRoleIds) {
                    $q->whereIn('role_ids', $userRoleIds);
                });
        })->with(['approvalWorkflow', 'requestedBy'])->get();
    }

    /**
     * Execute the approved action
     */
    public function executeApprovedAction(ApprovalRequest $approvalRequest): bool
    {
        if (!$approvalRequest->isApproved()) {
            return false;
        }

        try {
            // This is where the actual action would be executed
            // The action_data contains the information needed to execute the action
            $actionData = $approvalRequest->action_data;
            $action = $approvalRequest->action;

            // In a real implementation, you would have specific handlers for each action
            // For example:
            // switch ($action) {
            //     case 'Competition.update':
            //         return $this->executeCompetitionUpdate($actionData);
            //     case 'Event.create':
            //         return $this->executeEventCreate($actionData);
            //     // etc.
            // }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to execute approved action '{$approvalRequest->action}' for request {$approvalRequest->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel an approval request
     */
    public function cancelApprovalRequest(ApprovalRequest $approvalRequest, User $cancelledBy): bool
    {
        if (!$approvalRequest->isPending()) {
            return false;
        }

        $approvalRequest->cancel();

        return true;
    }

    /**
     * Get approval request statistics
     */
    public function getApprovalStatistics(): array
    {
        return [
            'pending' => ApprovalRequest::pending()->count(),
            'approved' => ApprovalRequest::approved()->count(),
            'rejected' => ApprovalRequest::rejected()->count(),
            'cancelled' => ApprovalRequest::cancelled()->count(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Program;
use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequestLevel;
use App\Events\ApprovalRequestStatusChanged;
use App\Events\ApproverAssignedToRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgramApprovalService
{
    /**
     * Check if an approval workflow exists for the given action
     */
    public function hasWorkflowForAction(string $actionType): bool
    {
        return ApprovalWorkflow::where('action', "Program.{$actionType}")
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the approval workflow for the given action
     */
    public function getWorkflowForAction(string $actionType): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::where('action', "Program.{$actionType}")
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create an approval request for a program action
     */
    public function createApprovalRequest(
        string $actionType,
        array $actionData,
        ?int $programId = null,
        string $reason = null,
        int $requestedBy = null
    ): ?ApprovalRequest {
        $requestedBy = $requestedBy ?? auth()->id();
        
        // If still null, try to get the first user as fallback
        if (!$requestedBy) {
            $firstUser = \App\Models\User::first();
            $requestedBy = $firstUser ? $firstUser->id : null;
        }
        
        if (!$requestedBy) {
            \Log::error('No user ID available for approval request creation');
            return null;
        }
        
        $workflow = $this->getWorkflowForAction($actionType);
        if (!$workflow) {
            return null;
        }

        try {
            DB::beginTransaction();

            $request = ApprovalRequest::create([
                'action' => "Program.{$actionType}",
                'target_type' => Program::class,
                'target_id' => $programId,
                'requested_by' => $requestedBy,
                'approval_workflow_id' => $workflow->id,
                'status' => 'pending',
                'reason' => $reason,
                'action_data' => array_merge($actionData, ['action_type' => $actionType]),
            ]);

            // Create approval levels
            $this->createApprovalLevels($request, $workflow);

            DB::commit();

            // Dispatch event
            event(new ApprovalRequestStatusChanged($request, 'created', 'pending'));
            
            // Send notifications to approvers
            $this->notifyApprovers($request);

            return $request;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create program approval request: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create approval levels for the request
     */
    protected function createApprovalLevels(ApprovalRequest $request, ApprovalWorkflow $workflow): void
    {
        $approvalLevels = $workflow->approvalLevels()->orderBy('level_number')->get();
        
        foreach ($approvalLevels as $level) {
            $requestLevel = ApprovalRequestLevel::create([
                'approval_request_id' => $request->id,
                'level_number' => $level->level_number,
                'role_ids' => $level->role_ids,
                'required_approvals' => $level->required_approvals,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Execute a program action immediately (when no workflow exists)
     */
    public function executeActionImmediately(
        string $actionType,
        array $actionData,
        ?int $programId = null
    ): bool {
        try {
            switch ($actionType) {
                case 'create':
                    Program::create($actionData);
                    break;
                case 'update':
                    if ($programId) {
                        Program::findOrFail($programId)->update($actionData);
                    }
                    break;
                case 'delete':
                    if ($programId) {
                        Program::findOrFail($programId)->delete();
                    }
                    break;
                case 'archive':
                    if ($programId) {
                        Program::findOrFail($programId)->update(['is_archived' => true]);
                    }
                    break;
                default:
                    return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to execute program action immediately: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process a program action (with or without workflow)
     */
    public function processAction(
        string $actionType,
        array $actionData,
        ?int $programId = null,
        string $reason = null,
        int $requestedBy = null
    ): array {
        $requestedBy = $requestedBy ?? auth()->id();

        // Check if workflow exists
        if ($this->hasWorkflowForAction($actionType)) {
            $request = $this->createApprovalRequest($actionType, $actionData, $programId, $reason, $requestedBy);
            
            if ($request) {
                return [
                    'success' => true,
                    'requires_approval' => true,
                    'request_id' => $request->id,
                    'message' => 'Request submitted for approval / تم تقديم الطلب للموافقة',
                ];
            } else {
                return [
                    'success' => false,
                    'requires_approval' => false,
                    'message' => 'Failed to create approval request / فشل في إنشاء طلب الموافقة',
                ];
            }
        } else {
            // Execute immediately
            $success = $this->executeActionImmediately($actionType, $actionData, $programId);
            
            return [
                'success' => $success,
                'requires_approval' => false,
                'message' => $success 
                    ? 'Action executed successfully / تم تنفيذ الإجراء بنجاح'
                    : 'Failed to execute action / فشل في تنفيذ الإجراء',
            ];
        }
    }

    /**
     * Execute approved program actions
     */
    public function executeApprovedActions(): int
    {
        $approvedRequests = ApprovalRequest::where('status', 'approved')
            ->where('target_type', Program::class)
            ->whereNull('executed_at')
            ->get();

        $executedCount = 0;

        foreach ($approvedRequests as $request) {
            if ($request->executeAction()) {
                $request->update(['executed_at' => now()]);
                $executedCount++;
            }
        }

        return $executedCount;
    }
    
    /**
     * Send notifications to approvers for a new approval request
     */
    protected function notifyApprovers(ApprovalRequest $request): void
    {
        try {
            // Get all users who can approve this request
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
     * Get approvers for a specific request
     */
    protected function getApproversForRequest(ApprovalRequest $request): \Illuminate\Support\Collection
    {
        // Get all role IDs from approval levels
        $roleIds = [];
        foreach ($request->approvalRequestLevels as $level) {
            $roleIds = array_merge($roleIds, $level->role_ids);
        }
        
        // Get unique role IDs
        $roleIds = array_unique($roleIds);
        
        // Get users with these roles
        return \App\Models\User::whereHas('roles', function($query) use ($roleIds) {
            $query->whereIn('id', $roleIds);
        })->get();
    }
    
    /**
     * Send notification to a specific approver
     */
    protected function sendApproverNotification(\App\Models\User $approver, ApprovalRequest $request): void
    {
        $level = $request->getCurrentLevel();
        $levelNumber = (int) ($level?->level_number ?? 1);

        event(new ApproverAssignedToRequest($request, $approver, $levelNumber));
    }
}

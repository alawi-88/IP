<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequestLevel;
use App\Events\ApprovalRequestStatusChanged;
use App\Events\ApproverAssignedToRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectApprovalService
{
    /**
     * Check if an approval workflow exists for the given action
     */
    public function hasWorkflowForAction(string $actionType): bool
    {
        return ApprovalWorkflow::where('action', "Project.{$actionType}")
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the approval workflow for the given action
     */
    public function getWorkflowForAction(string $actionType): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::where('action', "Project.{$actionType}")
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create an approval request for a project action
     */
    public function createApprovalRequest(
        string $actionType,
        array $actionData,
        ?int $projectId = null,
        string $reason = null,
        int $requestedBy = null,
        ?ApprovalWorkflow $workflowOverride = null
    ): ?ApprovalRequest {
        $requestedBy = $requestedBy ?? auth()->id();
        
        // If still null, try to get the first user as fallback
        if (!$requestedBy) {
            $firstUser = \App\Models\User::first();
            $requestedBy = $firstUser ? $firstUser->id : null;
        }
        
        if (!$requestedBy) {
            \Log::error('No user ID available for project approval request creation');
            return null;
        }
        
        $workflow = $workflowOverride ?: $this->getWorkflowForAction($actionType);
        if (!$workflow) {
            return null;
        }

        // Validate prerequisites based on action type
        if (!$this->validatePrerequisites($actionType, $projectId)) {
            return null;
        }

        // Check for existing pending request for the same project and action
        $existingRequest = ApprovalRequest::where('target_type', Project::class)
            ->where('target_id', $projectId)
            ->where('action', "Project.{$actionType}")
            ->where('status', 'pending')
            ->where('requested_by', $requestedBy)
            ->first();

        if ($existingRequest) {
            Log::warning("Duplicate approval request attempted for project {$projectId}, action {$actionType} by user {$requestedBy}");
            return null; // Prevent duplicate requests
        }

        try {
            DB::beginTransaction();

            $request = ApprovalRequest::create([
                'action' => "Project.{$actionType}",
                'target_type' => Project::class,
                'target_id' => $projectId,
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
            Log::error('Failed to create project approval request: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate prerequisites for project actions
     */
    protected function validatePrerequisites(string $actionType, ?int $projectId): bool
    {
        // For update, delete, archive, or restore, project must exist
        if (in_array($actionType, ['update', 'delete', 'archive', 'restore'])) {
            if (!$projectId) {
                Log::error("Project ID is required for {$actionType} action");
                return false;
            }

            $project = Project::find($projectId);
            if (!$project) {
                Log::error("Project not found with ID: {$projectId}");
                return false;
            }

            // For update, project should not be archived
            if ($actionType === 'update' && $project->is_archived) {
                Log::error("Cannot update archived project with ID: {$projectId}");
                return false;
            }

            // For archive, project should not already be archived
            if ($actionType === 'archive' && $project->is_archived) {
                Log::error("Project with ID {$projectId} is already archived");
                return false;
            }

            // For restore, project should be archived
            if ($actionType === 'restore' && !$project->is_archived) {
                Log::error("Project with ID {$projectId} is not archived; cannot restore");
                return false;
            }
        }

        return true;
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
     * Execute a project action immediately (when no workflow exists)
     */
    public function executeActionImmediately(
        string $actionType,
        array $actionData,
        ?int $projectId = null
    ): bool {
        try {
            switch ($actionType) {
                case 'update':
                    if ($projectId) {
                        $project = Project::findOrFail($projectId);
                        $project->update($actionData);
                    }
                    break;
                case 'delete':
                    if ($projectId) {
                        Project::findOrFail($projectId)->delete();
                    }
                    break;
                case 'archive':
                    if ($projectId) {
                        $project = Project::findOrFail($projectId);
                        $project->archive();
                    }
                    break;
                case 'restore':
                    if ($projectId) {
                        $project = Project::findOrFail($projectId);
                        $project->restore();
                    }
                    break;
                default:
                    return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to execute project action immediately: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process a project action (with or without workflow)
     */
    public function processAction(
        string $actionType,
        array $actionData,
        ?int $projectId = null,
        string $reason = null,
        int $requestedBy = null
    ): array {
        $requestedBy = $requestedBy ?? auth()->id();

        // Restore should require approval when either a dedicated restore workflow exists
        // OR when an archive workflow exists (shared policy).
        $workflowOverride = null;
        $requiresWorkflow = $this->hasWorkflowForAction($actionType);
        if ($actionType === 'restore' && !$requiresWorkflow) {
            $archiveWorkflow = $this->getWorkflowForAction('archive');
            if ($archiveWorkflow) {
                $requiresWorkflow = true;
                $workflowOverride = $archiveWorkflow;
            }
        }

        // Check if workflow exists
        if ($requiresWorkflow) {
            $request = $this->createApprovalRequest($actionType, $actionData, $projectId, $reason, $requestedBy, $workflowOverride);
            
            if ($request) {
                return [
                    'success' => true,
                    'requires_approval' => true,
                    'request_id' => $request->id,
                    'message' => 'Request submitted for approval / تم تقديم الطلب للموافقة',
                ];
            } else {
                // Check if it's a duplicate request
                $existingRequest = ApprovalRequest::where('target_type', Project::class)
                    ->where('target_id', $projectId)
                    ->where('action', "Project.{$actionType}")
                    ->where('status', 'pending')
                    ->where('requested_by', $requestedBy)
                    ->exists();
                
                if ($existingRequest) {
                    return [
                        'success' => false,
                        'requires_approval' => false,
                        'message' => 'A pending request already exists for this project and action / يوجد طلب معلق بالفعل لهذا المشروع والإجراء',
                    ];
                }
                
                return [
                    'success' => false,
                    'requires_approval' => false,
                    'message' => 'Failed to create approval request / فشل في إنشاء طلب الموافقة',
                ];
            }
        } else {
            // No workflow exists - execute immediately
            $success = $this->executeActionImmediately($actionType, $actionData, $projectId);
            
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
     * Execute approved project actions
     */
    public function executeApprovedActions(): int
    {
        $approvedRequests = ApprovalRequest::where('status', 'approved')
            ->where('target_type', Project::class)
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
            $level = $request->getCurrentLevel();
            $levelNumber = (int) ($level?->level_number ?? 1);

            // Prefer notifying the approvers who can act at the current level.
            $approvers = $this->getApproversForRequest($request);

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


<?php

namespace App\Services;

use App\Models\ProgramApplication;
use App\Models\Program;
use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequestLevel;
use App\Events\ApprovalRequestStatusChanged;
use App\Events\ApproverAssignedToRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ApplicationApprovalService
{
    /**
     * Check if an approval workflow exists for the given action
     */
    public function hasWorkflowForAction(string $actionType, string $modelType = 'ProgramApplication'): bool
    {
        return ApprovalWorkflow::where('action', "{$modelType}.{$actionType}")
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the approval workflow for the given action
     */
    public function getWorkflowForAction(string $actionType, string $modelType = 'ProgramApplication'): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::where('action', "{$modelType}.{$actionType}")
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create an approval request for an application action
     */
    public function createApprovalRequest(
        string $actionType,
        array $actionData,
        ?int $applicationId = null,
        string $reason = null,
        int $requestedBy = null,
        string $modelType = 'ProgramApplication'
    ): ?ApprovalRequest {
        $requestedBy = $requestedBy ?? auth()->id();
        
        // If still null, try to get the first user as fallback
        if (!$requestedBy) {
            $firstUser = \App\Models\User::first();
            $requestedBy = $firstUser ? $firstUser->id : null;
        }
        
        if (!$requestedBy) {
            \Log::error('No user ID available for application approval request creation');
            return null;
        }
        
        $workflow = $this->getWorkflowForAction($actionType, $modelType);
        if (!$workflow) {
            return null;
        }

        // Determine target type and class
        $targetType = $modelType === 'Program' ? Program::class : ProgramApplication::class;

        try {
            DB::beginTransaction();

            $request = ApprovalRequest::create([
                'action' => "{$modelType}.{$actionType}",
                'target_type' => $targetType,
                'target_id' => $applicationId,
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

            // Notify approvers (in-app bell + email), same behavior as other approval services.
            $this->notifyApprovers($request);

            return $request;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create application approval request: ' . $e->getMessage());
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
     * Execute an application action immediately (when no workflow exists)
     */
    public function executeActionImmediately(
        string $actionType,
        array $actionData,
        ?int $applicationId = null,
        string $modelType = 'ProgramApplication'
    ): bool {
        try {
            if ($modelType === 'Program') {
                switch ($actionType) {
                    case 'create':
                        Program::create($actionData);
                        break;
                    case 'update':
                        if ($applicationId) {
                            Program::findOrFail($applicationId)->update($actionData);
                        }
                        break;
                    case 'delete':
                        if ($applicationId) {
                            Program::findOrFail($applicationId)->delete();
                        }
                        break;
                    case 'archive':
                        if ($applicationId) {
                            Program::findOrFail($applicationId)->archive();
                        }
                        break;
                    default:
                        return false;
                }
            } else {
                switch ($actionType) {
                    case 'create':
                        ProgramApplication::create($actionData);
                        break;
                    case 'update':
                        if ($applicationId) {
                            ProgramApplication::findOrFail($applicationId)->update($actionData);
                        }
                        break;
                    case 'delete':
                        if ($applicationId) {
                            ProgramApplication::findOrFail($applicationId)->delete();
                        }
                        break;
                    case 'archive':
                        if ($applicationId) {
                            ProgramApplication::findOrFail($applicationId)->archive();
                        }
                        break;
                    default:
                        return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to execute application action immediately: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process an application action (with or without workflow)
     */
    public function processAction(
        string $actionType,
        array $actionData,
        ?int $applicationId = null,
        string $reason = null,
        int $requestedBy = null,
        string $modelType = 'ProgramApplication'
    ): array {
        $requestedBy = $requestedBy ?? auth()->id();


        // Check if workflow exists
        if ($this->hasWorkflowForAction($actionType, $modelType)) {
            $request = $this->createApprovalRequest($actionType, $actionData, $applicationId, $reason, $requestedBy, $modelType);
            
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
            $success = $this->executeActionImmediately($actionType, $actionData, $applicationId, $modelType);
            
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
     * Execute approved application actions
     */
    public function executeApprovedActions(): int
    {
        $approvedRequests = ApprovalRequest::where('status', 'approved')
            ->where('target_type', ProgramApplication::class)
            ->whereNull('executed_at')
            ->get();

        $executedCount = 0;

        foreach ($approvedRequests as $request) {
            if ($this->executeApprovedAction($request)) {
                $request->update(['executed_at' => now()]);
                $executedCount++;
            }
        }

        return $executedCount;
    }

    /**
     * Execute a single approved application action
     */
    public function executeApprovedAction(ApprovalRequest $request): bool
    {
        try {
            $actionData = $request->action_data ?? [];
            $actionType = $actionData['action_type'] ?? 'create';
            
            // Remove action_type from data as it's not part of the model data
            unset($actionData['action_type']);
            
            // Ensure required fields are present
            if ($actionType === 'create') {
                $actionData['registered_as'] = $actionData['registered_as'] ?? 'individual';
                $actionData['has_team'] = $actionData['has_team'] ?? false;
                $actionData['has_idea'] = $actionData['has_idea'] ?? false;
                $actionData['team_member_previous_participation'] = $actionData['team_member_previous_participation'] ?? false;
            }
            
            switch ($actionType) {
                case 'create':
                    $application = ProgramApplication::create($actionData);
                    // Update the approval request with the created application ID
                    $request->update(['target_id' => $application->id]);
                    break;
                case 'update':
                    if ($request->target_id) {
                        ProgramApplication::findOrFail($request->target_id)->update($actionData);
                    }
                    break;
                case 'delete':
                    if ($request->target_id) {
                        ProgramApplication::findOrFail($request->target_id)->delete();
                    }
                    break;
                case 'archive':
                    if ($request->target_id) {
                        ProgramApplication::findOrFail($request->target_id)->archive();
                    }
                    break;
                default:
                    return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to execute approved application action: ' . $e->getMessage());
            return false;
        }
    }
}

<?php

namespace App\Services;

use App\Models\Form;
use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequestLevel;
use App\Events\ApprovalRequestStatusChanged;
use App\Events\ApproverAssignedToRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FormApprovalService
{
    /**
     * Check if an approval workflow exists for the given action
     */
    public function hasWorkflowForAction(string $actionType): bool
    {
        return ApprovalWorkflow::where('action', "Form.{$actionType}")
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the approval workflow for the given action
     */
    public function getWorkflowForAction(string $actionType): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::where('action', "Form.{$actionType}")
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create an approval request for a form action
     */
    public function createApprovalRequest(
        string $actionType,
        array $actionData,
        ?int $formId = null,
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
            \Log::error('No user ID available for form approval request creation');
            return null;
        }
        
        $workflow = $this->getWorkflowForAction($actionType);
        if (!$workflow) {
            return null;
        }

        // Validate prerequisites based on action type
        if (!$this->validatePrerequisites($actionType, $formId)) {
            return null;
        }

        // Check for existing pending request for the same form and action
        if ($formId) {
            $existingRequest = ApprovalRequest::where('target_type', Form::class)
                ->where('target_id', $formId)
                ->where('action', "Form.{$actionType}")
                ->where('status', 'pending')
                ->where('requested_by', $requestedBy)
                ->first();

            if ($existingRequest) {
                Log::warning("Duplicate approval request attempted for form {$formId}, action {$actionType} by user {$requestedBy}");
                return null; // Prevent duplicate requests
            }
        }

        try {
            DB::beginTransaction();

            $request = ApprovalRequest::create([
                'action' => "Form.{$actionType}",
                'target_type' => Form::class,
                'target_id' => $formId,
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
            Log::error('Failed to create form approval request: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate prerequisites for form actions
     */
    protected function validatePrerequisites(string $actionType, ?int $formId): bool
    {
        // For create action, no form needs to exist
        if ($actionType === 'create') {
            return true;
        }

        // For update, delete, or archive, form must exist
        if (in_array($actionType, ['update', 'delete', 'archive'])) {
            if (!$formId) {
                Log::error("Form ID is required for {$actionType} action");
                return false;
            }

            $form = Form::find($formId);
            if (!$form) {
                Log::error("Form not found with ID: {$formId}");
                return false;
            }

            // For update, delete, or archive, form should be in editable state
            // (not already archived for update, or not already deleted)
            if ($actionType === 'update' && $form->is_archived) {
                Log::error("Cannot update archived form with ID: {$formId}");
                return false;
            }

            if ($actionType === 'archive' && $form->is_archived) {
                Log::error("Form with ID {$formId} is already archived");
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
     * Execute a form action immediately (when no workflow exists)
     */
    public function executeActionImmediately(
        string $actionType,
        array $actionData,
        ?int $formId = null
    ): bool {
        try {
            switch ($actionType) {
                case 'create':
                    Form::create($actionData);
                    break;
                case 'update':
                    if ($formId) {
                        Form::findOrFail($formId)->update($actionData);
                    }
                    break;
                case 'delete':
                    if ($formId) {
                        Form::findOrFail($formId)->delete();
                    }
                    break;
                case 'archive':
                    if ($formId) {
                        $form = Form::findOrFail($formId);
                        $form->archive();
                    }
                    break;
                default:
                    return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to execute form action immediately: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process a form action (with or without workflow)
     */
    public function processAction(
        string $actionType,
        array $actionData,
        ?int $formId = null,
        string $reason = null,
        int $requestedBy = null
    ): array {
        $requestedBy = $requestedBy ?? auth()->id();

        // Check if workflow exists
        if ($this->hasWorkflowForAction($actionType)) {
            $request = $this->createApprovalRequest($actionType, $actionData, $formId, $reason, $requestedBy);
            
            if ($request) {
                return [
                    'success' => true,
                    'requires_approval' => true,
                    'request_id' => $request->id,
                    'message' => 'Request submitted for approval / تم تقديم الطلب للموافقة',
                ];
            } else {
                // Check if it's a duplicate request
                if ($formId) {
                    $existingRequest = ApprovalRequest::where('target_type', Form::class)
                        ->where('target_id', $formId)
                        ->where('action', "Form.{$actionType}")
                        ->where('status', 'pending')
                        ->where('requested_by', $requestedBy)
                        ->exists();
                    
                    if ($existingRequest) {
                        return [
                            'success' => false,
                            'requires_approval' => false,
                            'message' => 'A pending request already exists for this form and action / يوجد طلب معلق بالفعل لهذا النموذج والإجراء',
                        ];
                    }
                }
                
                // Check if prerequisites are not met
                if (!$this->validatePrerequisites($actionType, $formId)) {
                    $errorMessage = match($actionType) {
                        'update' => 'Form not found or is archived / النموذج غير موجود أو مؤرشف',
                        'delete' => 'Form not found / النموذج غير موجود',
                        'archive' => 'Form not found or already archived / النموذج غير موجود أو مؤرشف بالفعل',
                        default => 'Prerequisites not met / المتطلبات غير مستوفاة',
                    };
                    
                    return [
                        'success' => false,
                        'requires_approval' => false,
                        'message' => $errorMessage,
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
            $success = $this->executeActionImmediately($actionType, $actionData, $formId);
            
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
     * Execute approved form actions
     */
    public function executeApprovedActions(): int
    {
        $approvedRequests = ApprovalRequest::where('status', 'approved')
            ->where('target_type', Form::class)
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

<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;

class ApprovalWorkflowService
{
    /**
     * Get all available actions that can have approval workflows
     */
    public function getAvailableActions(): array
    {
        return [
            'Competition.create' => 'Create Competition',
            'Competition.update' => 'Update Competition',
            'Competition.delete' => 'Delete Competition',
            'CompetitionApplication.update' => 'Update Competition Application',
            'CompetitionApplication.delete' => 'Delete Competition Application',
            'CompetitionApplication.archive' => 'Archive Competition Application',
            'Form.create' => 'Create Form',
            'Form.update' => 'Update Form',
            'Form.delete' => 'Delete Form',
            'Form.archive' => 'Archive Form',
            'Project.update' => 'Update Project',
            'Project.delete' => 'Delete Project',
            'Project.archive' => 'Archive Project',
            'Project.restore' => 'Restore Project',
            'User.create' => 'Create User',
            'User.update' => 'Update User',
            'User.delete' => 'Delete User',
            'Winner.create' => 'Create Winner',
            'Winner.update' => 'Update Winner',
            'Winner.delete' => 'Delete Winner',
            'Winner.toggle_visibility' => 'Toggle Winner Visibility',
        ];
    }

    /**
     * Check if a workflow exists for a specific action
     */
    public function hasWorkflowForAction(string $action): bool
    {
        return ApprovalWorkflow::where('action', $action)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get workflow for a specific action
     */
    public function getWorkflowForAction(string $action): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::where('action', $action)
            ->where('is_active', true)
            ->with('approvalLevels')
            ->first();
    }

    /**
     * Get all roles assigned to a workflow
     */
    public function getRolesForWorkflow(ApprovalWorkflow $workflow): Collection
    {
        $roleIds = $workflow->getAllRoles();
        return Role::whereIn('id', $roleIds)->get();
    }

    /**
     * Get roles for a specific level of a workflow
     */
    public function getRolesForLevel(ApprovalWorkflow $workflow, int $levelNumber): Collection
    {
        $level = $workflow->approvalLevels()
            ->where('level_number', $levelNumber)
            ->first();

        if (!$level) {
            return collect();
        }

        return Role::whereIn('id', $level->role_ids)->get();
    }

    /**
     * Check if a user has permission to approve at a specific level
     */
    public function canUserApproveAtLevel(User $user, ApprovalWorkflow $workflow, int $levelNumber): bool
    {
        $level = $workflow->approvalLevels()
            ->where('level_number', $levelNumber)
            ->first();

        if (!$level) {
            return false;
        }

        $userRoleIds = $user->roles->pluck('id')->toArray();
        return !empty(array_intersect($userRoleIds, $level->role_ids));
    }

    /**
     * Get the next level that needs approval
     */
    public function getNextApprovalLevel(ApprovalWorkflow $workflow, int $currentLevel = 0): ?ApprovalLevel
    {
        return $workflow->approvalLevels()
            ->where('level_number', '>', $currentLevel)
            ->orderBy('level_number')
            ->first();
    }

    /**
     * Check if a workflow is complete (all levels approved)
     */
    public function isWorkflowComplete(ApprovalWorkflow $workflow, int $approvedLevels): bool
    {
        return $approvedLevels >= $workflow->levels;
    }

    /**
     * Validate workflow configuration
     */
    public function validateWorkflow(array $data, bool $isEdit = false): array
    {
        $errors = [];

        // Check if action is valid
        if (!array_key_exists($data['action'], $this->getAvailableActions())) {
            $errors[] = 'Invalid action selected.';
        }

        // Check if levels is valid
        if (!isset($data['levels']) || $data['levels'] < 1) {
            $errors[] = 'Levels must be at least 1.';
        }

        // Check if roles exist and validate level assignments
        if (isset($data['approvalLevels'])) {
            foreach ($data['approvalLevels'] as $index => $level) {
                // Check if level has roles assigned
                if (empty($level['role_ids']) || count($level['role_ids']) === 0) {
                    $errors[] = "Level " . ($index + 1) . " must have at least one role assigned.";
                    continue;
                }

                // Check if roles exist
                if (isset($level['role_ids']) && !empty($level['role_ids'])) {
                    $existingRoleIds = Role::whereIn('id', $level['role_ids'])->pluck('id')->toArray();
                    $missingRoleIds = array_diff($level['role_ids'], $existingRoleIds);
                    
                    if (!empty($missingRoleIds)) {
                        $errors[] = "Some selected roles in level " . ($index + 1) . " do not exist.";
                        continue;
                    }
                }

                // Check if required approvals don't exceed available roles
                $requiredApprovals = $level['required_approvals'] ?? 1;
                $roleCount = count($level['role_ids']);
                
                if ($requiredApprovals > $roleCount) {
                    $errors[] = "Required approvals ({$requiredApprovals}) cannot exceed number of roles ({$roleCount}) for level " . ($index + 1) . ".";
                }
            }
        }

        return $errors;
    }

    /**
     * Create a new workflow with levels
     */
    public function createWorkflow(array $data): ApprovalWorkflow
    {
        // Validate the data
        $errors = $this->validateWorkflow($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        // Check if workflow already exists
        if ($this->hasWorkflowForAction($data['action'])) {
            throw new \InvalidArgumentException('A workflow already exists for this action.');
        }

        // Create the workflow
        $workflow = ApprovalWorkflow::create([
            'action' => $data['action'],
            'levels' => $data['levels'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Create approval levels
        if (isset($data['approvalLevels'])) {
            foreach ($data['approvalLevels'] as $levelData) {
                ApprovalLevel::create([
                    'approval_workflow_id' => $workflow->id,
                    'level_number' => $levelData['level_number'],
                    'role_ids' => $levelData['role_ids'] ?? [],
                    'required_approvals' => $levelData['required_approvals'] ?? 1,
                ]);
            }
        }

        return $workflow->load('approvalLevels');
    }

    /**
     * Update an existing workflow
     */
    public function updateWorkflow(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow
    {
        // Validate the data
        $errors = $this->validateWorkflow($data, true);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        // Store original levels for comparison
        $originalLevels = $workflow->levels;
        $newLevels = $data['levels'];

        // Update the workflow
        $workflow->update([
            'action' => $data['action'],
            'levels' => $newLevels,
            'is_active' => $data['is_active'] ?? $workflow->is_active,
        ]);

        // Delete existing levels
        $workflow->approvalLevels()->delete();

        // Create new levels
        if (isset($data['approvalLevels'])) {
            foreach ($data['approvalLevels'] as $levelData) {
                // Only create levels up to the new level count
                if ($levelData['level_number'] <= $newLevels) {
                    ApprovalLevel::create([
                        'approval_workflow_id' => $workflow->id,
                        'level_number' => $levelData['level_number'],
                        'role_ids' => $levelData['role_ids'] ?? [],
                        'required_approvals' => $levelData['required_approvals'] ?? 1,
                    ]);
                }
            }
        }

        return $workflow->load('approvalLevels');
    }

    /**
     * Delete a workflow and all its levels
     */
    public function deleteWorkflow(ApprovalWorkflow $workflow): bool
    {
        try {
            // Check if there are any active approval requests for this workflow
            // This would be implemented when approval requests are created
            // For now, we'll just log the deletion
            
            // Delete all approval levels first (cascade should handle this, but being explicit)
            $workflow->approvalLevels()->delete();
            
            // Delete the workflow
            $result = $workflow->delete();

            return $result;
        } catch (\Exception $e) {
            \Log::error("Failed to delete workflow '{$workflow->action}': " . $e->getMessage());
            throw new \Exception('Unable to delete workflow. Please try again later.');
        }
    }

    /**
     * Check if a workflow has active approval requests
     */
    public function hasActiveRequests(ApprovalWorkflow $workflow): bool
    {
        // This would be implemented when approval requests are created
        // For now, return false as there are no approval requests yet
        return false;
    }

    /**
     * Get warning message for workflow with active requests
     */
    public function getActiveRequestsWarning(ApprovalWorkflow $workflow): string
    {
        if ($this->hasActiveRequests($workflow)) {
            return 'Existing requests will continue under the old configuration.';
        }
        
        return '';
    }

    /**
     * Get workflow details for display
     */
    public function getWorkflowDetails(ApprovalWorkflow $workflow): array
    {
        $details = [
            'action' => $workflow->action,
            'action_display' => $this->getActionDisplayName($workflow->action),
            'is_active' => $workflow->is_active,
            'levels' => $workflow->levels,
            'created_at' => $workflow->created_at,
            'updated_at' => $workflow->updated_at,
            'approval_levels' => [],
        ];

        // Get approval levels with role details
        foreach ($workflow->approvalLevels as $level) {
            $roleDetails = $this->getRoleDetailsForLevel($level);
            
            $details['approval_levels'][] = [
                'level_number' => $level->level_number,
                'roles' => $roleDetails['roles'],
                'required_approvals' => $level->required_approvals,
                'has_unknown_roles' => $roleDetails['has_unknown_roles'],
                'has_deleted_roles' => $roleDetails['has_deleted_roles'],
            ];
        }

        return $details;
    }

    /**
     * Get action display name
     */
    public function getActionDisplayName(string $action): string
    {
        $actions = $this->getAvailableActions();
        return $actions[$action] ?? $action;
    }

    /**
     * Check if action is still available
     */
    public function isActionAvailable(string $action): bool
    {
        return array_key_exists($action, $this->getAvailableActions());
    }

    /**
     * Get role details for a specific level
     */
    public function getRoleDetailsForLevel(ApprovalLevel $level): array
    {
        $roleIds = $level->role_ids ?? [];
        $roles = Role::whereIn('id', $roleIds)->get();
        
        $existingRoleIds = $roles->pluck('id')->toArray();
        $missingRoleIds = array_diff($roleIds, $existingRoleIds);
        
        $roleDetails = [
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            })->toArray(),
            'has_unknown_roles' => !empty($missingRoleIds),
            'has_deleted_roles' => !empty($missingRoleIds),
            'missing_role_ids' => $missingRoleIds,
        ];

        return $roleDetails;
    }

    /**
     * Get workflow with all relationships loaded
     */
    public function getWorkflowWithDetails(int $workflowId): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::with('approvalLevels')->find($workflowId);
    }
}

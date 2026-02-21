<?php

namespace App\Console\Commands;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use Spatie\Permission\Models\Role;
use App\Services\ApprovalWorkflowService;
use Illuminate\Console\Command;

class TestApprovalWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:approval-workflows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the approval workflow system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Approval Workflow System...');
        $this->newLine();

        // Test 1: List all workflows
        $this->info('1. Listing all approval workflows:');
        $workflows = ApprovalWorkflow::with('approvalLevels')->get();
        
        if ($workflows->isEmpty()) {
            $this->warn('No approval workflows found.');
        } else {
            foreach ($workflows as $workflow) {
                $this->line("   - Action: {$workflow->action}");
                $this->line("     Levels: {$workflow->levels}");
                $this->line("     Status: " . ($workflow->is_active ? 'Active' : 'Inactive'));
                
                foreach ($workflow->approvalLevels as $level) {
                    $roleNames = $level->getRoleNames();
                    $roleNames = empty($roleNames) ? ['Unknown Role'] : $roleNames;
                    $this->line("       Level {$level->level_number}: " . implode(', ', $roleNames) . " (Required: {$level->required_approvals})");
                }
                $this->newLine();
            }
        }

        // Test 2: Test service methods
        $this->info('2. Testing ApprovalWorkflowService:');
        $service = new ApprovalWorkflowService();
        
        // Test available actions
        $actions = $service->getAvailableActions();
        $this->line("   Available actions: " . implode(', ', array_keys($actions)));
        
        // Test workflow existence
        $testAction = 'Program.update';
        $hasWorkflow = $service->hasWorkflowForAction($testAction);
        $this->line("   Has workflow for '{$testAction}': " . ($hasWorkflow ? 'Yes' : 'No'));
        
        if ($hasWorkflow) {
            $workflow = $service->getWorkflowForAction($testAction);
            $this->line("   Workflow details: {$workflow->action} with {$workflow->levels} levels");
        }

        // Test 3: Test role assignments
        $this->info('3. Testing role assignments:');
        $roles = Role::all();
        $this->line("   Available roles: " . $roles->pluck('name')->implode(', '));
        
        foreach ($workflows as $workflow) {
            $allRoles = $service->getRolesForWorkflow($workflow);
            $this->line("   Roles for '{$workflow->action}': " . $allRoles->pluck('name')->implode(', '));
        }

        $this->newLine();
        $this->info('Approval Workflow System test completed!');
    }
}

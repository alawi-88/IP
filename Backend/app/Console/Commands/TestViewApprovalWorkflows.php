<?php

namespace App\Console\Commands;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use Spatie\Permission\Models\Role;
use App\Services\ApprovalWorkflowService;
use Illuminate\Console\Command;

class TestViewApprovalWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:view-approval-workflows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the view approval workflow functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing View Approval Workflow Functionality...');
        $this->newLine();

        // Test 1: Find existing workflows to view
        $this->info('1. Finding existing workflows to view:');
        $workflows = ApprovalWorkflow::with('approvalLevels')->get();
        
        if ($workflows->isEmpty()) {
            $this->warn('No existing workflows found. Please create a workflow first.');
            return;
        }

        foreach ($workflows as $workflow) {
            $this->line("   Found workflow: {$workflow->action} with {$workflow->levels} levels");
        }

        // Test 2: Test service methods for detail view
        $this->info('2. Testing service methods for detail view:');
        $service = new ApprovalWorkflowService();
        
        $workflow = $workflows->first();
        
        // Test getWorkflowDetails
        $details = $service->getWorkflowDetails($workflow);
        $this->line("   Workflow details for '{$workflow->action}':");
        $this->line("     Action: {$details['action']}");
        $this->line("     Display Name: {$details['action_display']}");
        $this->line("     Status: " . ($details['is_active'] ? 'Active' : 'Inactive'));
        $this->line("     Levels: {$details['levels']}");
        $this->line("     Created: {$details['created_at']}");
        $this->line("     Updated: {$details['updated_at']}");

        // Test 3: Test action availability check
        $this->info('3. Testing action availability check:');
        $isAvailable = $service->isActionAvailable($workflow->action);
        $this->line("   Action '{$workflow->action}' is available: " . ($isAvailable ? 'Yes' : 'No'));
        
        $actionDisplayName = $service->getActionDisplayName($workflow->action);
        $this->line("   Display name: {$actionDisplayName}");

        // Test 4: Test role details for each level
        $this->info('4. Testing role details for each level:');
        foreach ($workflow->approvalLevels as $level) {
            $roleDetails = $service->getRoleDetailsForLevel($level);
            $this->line("   Level {$level->level_number}:");
            $this->line("     Roles: " . json_encode($roleDetails['roles']));
            $this->line("     Has unknown roles: " . ($roleDetails['has_unknown_roles'] ? 'Yes' : 'No'));
            $this->line("     Required approvals: {$level->required_approvals}");
        }

        // Test 5: Test workflow with details loading
        $this->info('5. Testing workflow with details loading:');
        $workflowWithDetails = $service->getWorkflowWithDetails($workflow->id);
        if ($workflowWithDetails) {
            $this->line("   ✓ Workflow loaded with relationships");
            $this->line("     Levels loaded: " . $workflowWithDetails->approvalLevels->count());
        } else {
            $this->line("   ✗ Failed to load workflow with details");
        }

        // Test 6: Test edge cases
        $this->info('6. Testing edge cases:');
        
        // Test with non-existent workflow
        $nonExistentWorkflow = $service->getWorkflowWithDetails(99999);
        if ($nonExistentWorkflow === null) {
            $this->line("   ✓ Correctly handled non-existent workflow");
        } else {
            $this->line("   ✗ Should have returned null for non-existent workflow");
        }

        // Test action availability for non-existent action
        $isNonExistentAvailable = $service->isActionAvailable('NonExistent.action');
        if (!$isNonExistentAvailable) {
            $this->line("   ✓ Correctly identified non-existent action as unavailable");
        } else {
            $this->line("   ✗ Should have identified non-existent action as unavailable");
        }

        // Test 7: Test role validation
        $this->info('7. Testing role validation:');
        
        // Create a test level with non-existent roles
        $testLevel = new ApprovalLevel([
            'role_ids' => [99999, 99998], // Non-existent role IDs
        ]);
        
        $roleDetails = $service->getRoleDetailsForLevel($testLevel);
        if ($roleDetails['has_unknown_roles']) {
            $this->line("   ✓ Correctly identified unknown roles");
            $this->line("     Missing role IDs: " . implode(', ', $roleDetails['missing_role_ids']));
        } else {
            $this->line("   ✗ Should have identified unknown roles");
        }

        // Test 8: Test detail view display format
        $this->info('8. Testing detail view display format:');
        $this->line("   Workflow Details Display:");
        $this->line("   =========================");
        $this->line("   Action: {$details['action_display']} ({$details['action']})");
        $this->line("   Status: " . ($details['is_active'] ? 'Active' : 'Inactive'));
        $this->line("   Levels: {$details['levels']}");
        $this->line("   Created: {$details['created_at']->format('M d, Y H:i')}");
        $this->line("   Updated: {$details['updated_at']->format('M d, Y H:i')}");
        $this->line("   ");
        $this->line("   Approval Levels:");
        
        foreach ($details['approval_levels'] as $level) {
            $this->line("     Level {$level['level_number']}:");
            $this->line("       Roles: " . implode(', ', array_column($level['roles'], 'name')));
            if ($level['has_unknown_roles']) {
                $this->line("       ⚠️  Unknown roles detected");
            }
            $this->line("       Required Approvals: {$level['required_approvals']}");
        }

        $this->newLine();
        $this->info('View Approval Workflow functionality test completed!');
        $this->newLine();
        $this->info('Key features tested:');
        $this->line('✓ Workflow details retrieval');
        $this->line('✓ Action availability checking');
        $this->line('✓ Role details validation');
        $this->line('✓ Unknown role detection');
        $this->line('✓ Display format generation');
        $this->line('✓ Error handling');
        $this->line('✓ Edge case scenarios');
    }
}

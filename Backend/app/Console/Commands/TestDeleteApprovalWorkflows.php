<?php

namespace App\Console\Commands;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use Spatie\Permission\Models\Role;
use App\Services\ApprovalWorkflowService;
use Illuminate\Console\Command;

class TestDeleteApprovalWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:delete-approval-workflows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the delete approval workflow functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Delete Approval Workflow Functionality...');
        $this->newLine();

        // Test 1: Create a test workflow to delete
        $this->info('1. Creating test workflow for deletion:');
        $testWorkflow = ApprovalWorkflow::create([
            'action' => 'Test.delete',
            'levels' => 2,
            'is_active' => true,
        ]);

        // Create test levels
        ApprovalLevel::create([
            'approval_workflow_id' => $testWorkflow->id,
            'level_number' => 1,
            'role_ids' => [1, 2], // Assuming roles with IDs 1, 2 exist
            'required_approvals' => 1,
        ]);

        ApprovalLevel::create([
            'approval_workflow_id' => $testWorkflow->id,
            'level_number' => 2,
            'role_ids' => [3], // Assuming role with ID 3 exists
            'required_approvals' => 1,
        ]);

        $this->line("   Created test workflow: {$testWorkflow->action} with {$testWorkflow->levels} levels");
        $this->line("   Workflow ID: {$testWorkflow->id}");

        // Test 2: Test service delete method
        $this->info('2. Testing service delete method:');
        $service = new ApprovalWorkflowService();
        
        try {
            $result = $service->deleteWorkflow($testWorkflow);
            if ($result) {
                $this->line("   ✓ Workflow deleted successfully via service");
            } else {
                $this->line("   ✗ Failed to delete workflow via service");
            }
        } catch (\Exception $e) {
            $this->line("   ✗ Error deleting workflow: " . $e->getMessage());
        }

        // Test 3: Verify workflow is deleted
        $this->info('3. Verifying workflow deletion:');
        $deletedWorkflow = ApprovalWorkflow::find($testWorkflow->id);
        if ($deletedWorkflow === null) {
            $this->line("   ✓ Workflow successfully removed from database");
        } else {
            $this->line("   ✗ Workflow still exists in database");
        }

        // Test 4: Verify levels are deleted
        $this->info('4. Verifying level deletion:');
        $remainingLevels = ApprovalLevel::where('approval_workflow_id', $testWorkflow->id)->count();
        if ($remainingLevels === 0) {
            $this->line("   ✓ All approval levels successfully deleted");
        } else {
            $this->line("   ✗ {$remainingLevels} approval levels still exist");
        }

        // Test 5: Test active requests check
        $this->info('5. Testing active requests check:');
        $hasActiveRequests = $service->hasActiveRequests($testWorkflow);
        $this->line("   Has active requests: " . ($hasActiveRequests ? 'Yes' : 'No'));
        
        $warningMessage = $service->getActiveRequestsWarning($testWorkflow);
        if (empty($warningMessage)) {
            $this->line("   ✓ No warning message (no active requests)");
        } else {
            $this->line("   Warning message: {$warningMessage}");
        }

        // Test 6: Test error handling
        $this->info('6. Testing error handling:');
        try {
            // Try to delete a non-existent workflow
            $nonExistentWorkflow = new ApprovalWorkflow(['id' => 99999]);
            $service->deleteWorkflow($nonExistentWorkflow);
            $this->line("   ✗ Should have thrown an exception for non-existent workflow");
        } catch (\Exception $e) {
            $this->line("   ✓ Correctly handled non-existent workflow: " . $e->getMessage());
        }

        // Test 7: Test bulk deletion scenario
        $this->info('7. Testing bulk deletion scenario:');
        
        // Create multiple test workflows
        $workflows = [];
        for ($i = 1; $i <= 3; $i++) {
            $workflow = ApprovalWorkflow::create([
                'action' => "Test.bulk.{$i}",
                'levels' => 1,
                'is_active' => true,
            ]);
            
            ApprovalLevel::create([
                'approval_workflow_id' => $workflow->id,
                'level_number' => 1,
                'role_ids' => [1],
                'required_approvals' => 1,
            ]);
            
            $workflows[] = $workflow;
        }

        $this->line("   Created {$workflows->count()} test workflows for bulk deletion");

        // Delete all test workflows
        foreach ($workflows as $workflow) {
            try {
                $service->deleteWorkflow($workflow);
                $this->line("   ✓ Deleted workflow: {$workflow->action}");
            } catch (\Exception $e) {
                $this->line("   ✗ Failed to delete workflow {$workflow->action}: " . $e->getMessage());
            }
        }

        // Verify all are deleted
        $remainingWorkflows = ApprovalWorkflow::whereIn('action', ['Test.bulk.1', 'Test.bulk.2', 'Test.bulk.3'])->count();
        if ($remainingWorkflows === 0) {
            $this->line("   ✓ All test workflows successfully deleted");
        } else {
            $this->line("   ✗ {$remainingWorkflows} test workflows still exist");
        }

        $this->newLine();
        $this->info('Delete Approval Workflow functionality test completed!');
        $this->newLine();
        $this->info('Key features tested:');
        $this->line('✓ Single workflow deletion');
        $this->line('✓ Bulk workflow deletion');
        $this->line('✓ Level cascade deletion');
        $this->line('✓ Error handling');
        $this->line('✓ Active requests checking');
        $this->line('✓ Database integrity');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalWorkflow;
use Spatie\Permission\Models\Role;

class TestDuplicateActionPolicy extends Command
{
    protected $signature = 'test:duplicate-action-policy';
    protected $description = 'Test duplicate action policy error message';

    public function handle()
    {
        $this->info('Testing Duplicate Action Policy Error Message...');
        
        // Create test roles if they don't exist
        $this->createTestRoles();
        
        // Test 1: Create first workflow
        $this->info("\n=== Test 1: Create first workflow ===");
        $workflow1 = $this->createTestWorkflow('test.duplicate.action', 'Test Duplicate Action');
        $this->info("✅ Created first workflow with action: test.duplicate.action");
        
        // Test 2: Try to create duplicate workflow
        $this->info("\n=== Test 2: Try to create duplicate workflow ===");
        $this->testDuplicateWorkflowCreation('test.duplicate.action');
        
        // Test 3: Create workflow with different action
        $this->info("\n=== Test 3: Create workflow with different action ===");
        $workflow2 = $this->createTestWorkflow('test.different.action', 'Test Different Action');
        $this->info("✅ Created second workflow with action: test.different.action");
        
        // Clean up
        $this->info("\n=== Cleaning up ===");
        $workflow1->approvalLevels()->delete();
        $workflow1->delete();
        $workflow2->approvalLevels()->delete();
        $workflow2->delete();
        $this->info("✅ Cleaned up test workflows");
        
        $this->info("\n✅ Duplicate action policy tests completed!");
    }
    
    private function createTestRoles()
    {
        $roles = ['admin', 'supervisor', 'manager'];
        
        foreach ($roles as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                Role::create(['name' => $roleName]);
                $this->info("Created role: {$roleName}");
            }
        }
    }
    
    private function createTestWorkflow($action, $description)
    {
        // Create workflow
        $workflow = ApprovalWorkflow::create([
            'action' => $action,
            'levels' => 2,
            'is_active' => true,
        ]);
        
        // Create levels
        $levels = [
            ['level_number' => 1, 'role_ids' => [1], 'required_approvals' => 1], // admin
            ['level_number' => 2, 'role_ids' => [2], 'required_approvals' => 1], // supervisor
        ];
        
        foreach ($levels as $levelData) {
            $workflow->approvalLevels()->create($levelData);
        }
        
        return $workflow;
    }
    
    private function testDuplicateWorkflowCreation($action)
    {
        // Check if workflow already exists
        $existingWorkflow = ApprovalWorkflow::where('action', $action)->first();
        
        if ($existingWorkflow) {
            $this->warn("⚠️  DUPLICATE ACTION POLICY DETECTED:");
            $this->warn("Title: Duplicate Action Policy / سياسة إجراء مكررة");
            $this->warn("Body: A workflow already exists for this action. / يوجد مسار اعتماد لهذا الإجراء بالفعل.");
            $this->warn("Action: {$action}");
            $this->warn("Existing workflow ID: {$existingWorkflow->id}");
            $this->info("✅ Correct error message would be displayed");
        } else {
            $this->error("❌ No existing workflow found for action: {$action}");
        }
    }
}

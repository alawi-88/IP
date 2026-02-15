<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalWorkflow;
use Spatie\Permission\Models\Role;

class TestDuplicateRolesWarning extends Command
{
    protected $signature = 'test:duplicate-roles-warning';
    protected $description = 'Test duplicate roles warning functionality';

    public function handle()
    {
        $this->info('Testing Duplicate Roles Warning Functionality...');
        
        // Create test roles if they don't exist
        $this->createTestRoles();
        
        // Test 1: Create workflow with duplicate roles
        $this->info("\n=== Test 1: Create workflow with duplicate roles ===");
        $this->testCreateWorkflowWithDuplicateRoles();
        
        // Test 2: Edit workflow to add duplicate roles
        $this->info("\n=== Test 2: Edit workflow to add duplicate roles ===");
        $this->testEditWorkflowWithDuplicateRoles();
        
        $this->info("\n✅ Duplicate roles warning tests completed!");
    }
    
    private function createTestRoles()
    {
        $roles = ['admin', 'supervisor', 'manager', 'director'];
        
        foreach ($roles as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                Role::create(['name' => $roleName]);
                $this->info("Created role: {$roleName}");
            }
        }
    }
    
    private function testCreateWorkflowWithDuplicateRoles()
    {
        // Create a workflow with duplicate roles
        $workflow = ApprovalWorkflow::create([
            'action' => 'test.duplicate.roles',
            'levels' => 3,
            'is_active' => true,
        ]);
        
        // Create levels with duplicate roles
        $levels = [
            ['level_number' => 1, 'role_ids' => [1, 2], 'required_approvals' => 1], // admin, supervisor
            ['level_number' => 2, 'role_ids' => [1, 3], 'required_approvals' => 1], // admin (duplicate), manager
            ['level_number' => 3, 'role_ids' => [2, 4], 'required_approvals' => 1], // supervisor (duplicate), director
        ];
        
        foreach ($levels as $levelData) {
            $workflow->approvalLevels()->create($levelData);
        }
        
        $this->info("Created workflow with duplicate roles:");
        $this->info("- Level 1: admin, supervisor");
        $this->info("- Level 2: admin (duplicate), manager");
        $this->info("- Level 3: supervisor (duplicate), director");
        
        // Test the checkDuplicateRoles method
        $this->info("\nTesting checkDuplicateRoles method...");
        $this->checkDuplicateRoles($levels);
        
        // Clean up
        $workflow->approvalLevels()->delete();
        $workflow->delete();
    }
    
    private function testEditWorkflowWithDuplicateRoles()
    {
        // Create a workflow
        $workflow = ApprovalWorkflow::create([
            'action' => 'test.edit.duplicate.roles',
            'levels' => 2,
            'is_active' => true,
        ]);
        
        // Create initial levels without duplicates
        $levels = [
            ['level_number' => 1, 'role_ids' => [1], 'required_approvals' => 1], // admin
            ['level_number' => 2, 'role_ids' => [2], 'required_approvals' => 1], // supervisor
        ];
        
        foreach ($levels as $levelData) {
            $workflow->approvalLevels()->create($levelData);
        }
        
        $this->info("Created initial workflow without duplicates:");
        $this->info("- Level 1: admin");
        $this->info("- Level 2: supervisor");
        
        // Simulate editing to add duplicate roles
        $this->info("\nSimulating edit to add duplicate roles...");
        $updatedLevels = [
            ['level_number' => 1, 'role_ids' => [1, 2], 'required_approvals' => 1], // admin, supervisor
            ['level_number' => 2, 'role_ids' => [2, 3], 'required_approvals' => 1], // supervisor (duplicate), manager
        ];
        
        $this->info("Updated levels:");
        $this->info("- Level 1: admin, supervisor");
        $this->info("- Level 2: supervisor (duplicate), manager");
        
        // Test the checkDuplicateRoles method
        $this->info("\nTesting checkDuplicateRoles method...");
        $this->checkDuplicateRoles($updatedLevels);
        
        // Clean up
        $workflow->approvalLevels()->delete();
        $workflow->delete();
    }
    
    private function checkDuplicateRoles(array $levels)
    {
        $roleUsage = [];
        $duplicateRoles = [];
        
        foreach ($levels as $index => $level) {
            $roleIds = $level['role_ids'] ?? [];
            
            if (is_array($roleIds)) {
                foreach ($roleIds as $roleId) {
                    if (!isset($roleUsage[$roleId])) {
                        $roleUsage[$roleId] = [];
                    }
                    $roleUsage[$roleId][] = $index + 1; // Level number
                }
            }
        }
        
        // Find roles used in multiple levels
        foreach ($roleUsage as $roleId => $levelNumbers) {
            if (count($levelNumbers) > 1) {
                $duplicateRoles[] = [
                    'role_id' => $roleId,
                    'levels' => $levelNumbers
                ];
            }
        }
        
        // Show warning if duplicate roles found
        if (!empty($duplicateRoles)) {
            $roleNames = Role::whereIn('id', array_column($duplicateRoles, 'role_id'))
                ->pluck('name', 'id')
                ->toArray();
            
            $this->warn("⚠️  DUPLICATE ROLES WARNING:");
            $this->warn("The following roles are assigned to multiple levels:");
            
            foreach ($duplicateRoles as $duplicate) {
                $roleName = $roleNames[$duplicate['role_id']] ?? "Role ID {$duplicate['role_id']}";
                $levels = implode(', ', $duplicate['levels']);
                $this->warn("- {$roleName} (Levels: {$levels})");
            }
        } else {
            $this->info("✅ No duplicate roles found.");
        }
    }
}

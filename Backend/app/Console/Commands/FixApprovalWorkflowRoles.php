<?php

namespace App\Console\Commands;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use Spatie\Permission\Models\Role;
use Illuminate\Console\Command;

class FixApprovalWorkflowRoles extends Command
{
    protected $signature = 'fix:approval-workflow-roles';
    protected $description = 'Fix approval workflow roles data';

    public function handle()
    {
        $this->info('🔧 Fixing Approval Workflow Roles...');
        $this->newLine();

        // Check if there are any roles in the system
        $roles = Role::all();
        $this->line("Found {$roles->count()} roles in the system:");
        foreach ($roles as $role) {
            $this->line("  - {$role->id}: {$role->name}");
        }

        if ($roles->isEmpty()) {
            $this->warn('No roles found. Creating default roles...');
            $this->createDefaultRoles();
            $roles = Role::all();
        }

        // Check workflows
        $workflows = ApprovalWorkflow::with('approvalLevels')->get();
        $this->line("\nFound {$workflows->count()} workflows");

        foreach ($workflows as $workflow) {
            $this->line("\n--- Workflow: {$workflow->action} ---");
            
            $levels = $workflow->approvalLevels;
            $this->line("Levels: {$levels->count()}");
            
            foreach ($levels as $level) {
                $this->line("  Level {$level->level_number}:");
                $this->line("    Raw role_ids: " . json_encode($level->role_ids));
                
                // Fix role_ids if needed
                $roleIds = $level->role_ids;
                if (is_string($roleIds)) {
                    $roleIds = json_decode($roleIds, true);
                    $level->role_ids = $roleIds;
                    $level->save();
                    $this->line("    ✅ Fixed role_ids format");
                }
                
                $roleNames = $level->getRoleNames();
                $this->line("    Role names: " . json_encode($roleNames));
                
                if (empty($roleNames)) {
                    $this->warn("    ⚠️ No roles assigned to this level");
                    
                    // Assign default roles if none exist
                    if ($roles->isNotEmpty()) {
                        $defaultRoleIds = $roles->take(2)->pluck('id')->toArray();
                        $level->role_ids = $defaultRoleIds;
                        $level->save();
                        $this->line("    ✅ Assigned default roles: " . json_encode($defaultRoleIds));
                    }
                }
            }
        }

        $this->newLine();
        $this->info('✅ Approval Workflow Roles fix completed!');
    }
    
    private function createDefaultRoles()
    {
        $defaultRoles = [
            ['name' => 'Supervisor', 'guard_name' => 'web'],
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['name' => 'Director', 'guard_name' => 'web'],
            ['name' => 'Admin', 'guard_name' => 'web'],
        ];
        
        foreach ($defaultRoles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
        
        $this->line('Default roles created successfully');
    }
}

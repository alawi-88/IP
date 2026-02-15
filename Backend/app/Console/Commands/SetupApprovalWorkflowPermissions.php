<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupApprovalWorkflowPermissions extends Command
{
    protected $signature = 'setup:approval-workflow-permissions';
    protected $description = 'Setup permissions for Approval Workflow';

    public function handle()
    {
        $this->info('Setting up Approval Workflow permissions...');

        $permissions = [
            'view ApprovalPolicies',
            'create ApprovalPolicies',
            'update ApprovalPolicies',
            'delete ApprovalPolicies',
        ];

        // Create permissions and collect them
        $createdPermissions = [];
        foreach ($permissions as $permission) {
            $createdPermission = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            $createdPermissions[] = $createdPermission;
            $this->line("✓ Permission: {$permission}");
        }

        // Assign to Super Admin
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            try {
                $superAdminRole->givePermissionTo($createdPermissions);
                $this->info("✓ Assigned to Super Admin role");
            } catch (\Exception $e) {
                $this->error("Failed to assign to Super Admin: " . $e->getMessage());
            }
        } else {
            $this->warn("Super Admin role not found");
        }

        // Assign to Admin
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            try {
                $adminRole->givePermissionTo($createdPermissions);
                $this->info("✓ Assigned to Admin role");
            } catch (\Exception $e) {
                $this->error("Failed to assign to Admin: " . $e->getMessage());
            }
        } else {
            $this->warn("Admin role not found");
        }

        $this->info('Approval Workflow permissions setup completed!');
    }
}

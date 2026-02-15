<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ApprovalRequestPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for ApprovalRequest
        $permissions = [
            'view ApprovalRequest',
            'update ApprovalRequest',
            'delete ApprovalRequest',
            'approve ApprovalRequest',
            'reject ApprovalRequest',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Also create the standard Filament permissions
        $filamentPermissions = [
            'view_any_approval_request',
            'view_approval_request',
            'edit_approval_request',
            'delete_approval_request',
            'delete_any_approval_request',
        ];

        foreach ($filamentPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Combine all permissions
        $allPermissions = array_merge($permissions, $filamentPermissions);

        // Assign permissions to roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($allPermissions);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo($allPermissions);

        // Manager role can view and approve/reject but not delete
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo([
            'view ApprovalRequest',
            'view_any_approval_request',
            'view_approval_request',
            'approve ApprovalRequest',
            'reject ApprovalRequest',
        ]);
    }
}

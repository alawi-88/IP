<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NafathPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for Nafath settings
        $permissions = [
            'configure Integrations',
        ];

        $createdPermissions = [];
        foreach ($permissions as $permission) {
            $createdPermission = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            $createdPermissions[] = $createdPermission;
            $this->command->info("✓ Permission created: {$permission}");
        }

        // Assign permissions to super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            try {
                $superAdminRole->givePermissionTo($createdPermissions);
                $this->command->info("✓ Permissions assigned to super-admin role");
            } catch (\Exception $e) {
                $this->command->error("Failed to assign permissions to super-admin: " . $e->getMessage());
            }
        } else {
            $this->command->warn("Super-admin role not found");
        }

        // Assign permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            try {
                $adminRole->givePermissionTo($createdPermissions);
                $this->command->info("✓ Permissions assigned to admin role");
            } catch (\Exception $e) {
                $this->command->error("Failed to assign permissions to admin: " . $e->getMessage());
            }
        } else {
            $this->command->warn("Admin role not found");
        }

        $this->command->info('Nafath permissions created and assigned successfully.');
    }
}

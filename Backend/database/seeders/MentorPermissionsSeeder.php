<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MentorPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions for Mentor Sessions
        $sessionPermissions = [
            'view MentorSessions',
            'update MentorSessions',
            'delete MentorSessions',
        ];

        foreach ($sessionPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Permissions for Video Tool Integrations
        $videoToolPermissions = [
            'view VideoToolIntegrations',
            'update VideoToolIntegrations',
            'delete VideoToolIntegrations',
        ];

        foreach ($videoToolPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to Super Admin role (if exists)
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(array_merge($sessionPermissions, $videoToolPermissions));
        }

        // Assign view permissions to Admin role (if exists)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'view MentorSessions',
                'view VideoToolIntegrations',
            ]);
        }

        $this->command->info('Mentor Sessions and Video Tool Integrations permissions have been created!');
    }
}

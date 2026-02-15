<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class CleanupUnimplementedPermissions extends Command
{
    protected $signature = 'permissions:cleanup-unimplemented';
    protected $description = 'Remove unimplemented permissions from database';

    public function handle()
    {
        $this->info('Cleaning up unimplemented permissions...');

        $unimplementedPermissions = [
            'archive ApprovalPolicies',
            'Restore ApprovalPolicies',
        ];

        $removedCount = 0;
        foreach ($unimplementedPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            
            if ($permission) {
                $permission->delete();
                $this->line("✓ Removed: {$permissionName}");
                $removedCount++;
            } else {
                $this->line("⚠ Not found: {$permissionName}");
            }
        }

        $this->info("Cleanup completed! Removed {$removedCount} permissions.");
    }
}

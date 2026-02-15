<?php

namespace App\Console\Commands;

use App\Models\ApprovalLevel;
use App\Models\ApprovalWorkflow;
use Spatie\Permission\Models\Role;
use Illuminate\Console\Command;

class CheckApprovalLevelsData extends Command
{
    protected $signature = 'check:approval-levels-data';
    protected $description = 'Check approval levels data and roles';

    public function handle()
    {
        $this->info('🔍 Checking approval levels data...');
        
        // Check workflows
        $workflows = ApprovalWorkflow::with('approvalLevels')->get();
        $this->line("Found {$workflows->count()} workflows");
        
        foreach ($workflows as $workflow) {
            $this->line("\nWorkflow: {$workflow->action} (ID: {$workflow->id})");
            $this->line("Levels: {$workflow->levels}");
            
            $levels = $workflow->approvalLevels;
            $this->line("Actual levels in DB: {$levels->count()}");
            
            foreach ($levels as $level) {
                $this->line("  Level {$level->level_number}:");
                $this->line("    role_ids: " . json_encode($level->role_ids));
                $this->line("    role_ids type: " . gettype($level->role_ids));
                
                if (!empty($level->role_ids)) {
                    $roleIds = is_array($level->role_ids) ? $level->role_ids : json_decode($level->role_ids, true) ?? [];
                    $this->line("    processed role_ids: " . json_encode($roleIds));
                    
                    if (!empty($roleIds)) {
                        $roles = Role::whereIn('id', $roleIds)->get();
                        $this->line("    found roles: " . $roles->pluck('name')->implode(', '));
                    }
                }
            }
        }
        
        // Check roles
        $roles = Role::all();
        $this->line("\nAvailable roles:");
        foreach ($roles as $role) {
            $this->line("  ID: {$role->id}, Name: {$role->name}");
        }
        
        $this->info('✅ Check completed!');
    }
}

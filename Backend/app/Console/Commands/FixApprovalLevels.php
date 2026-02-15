<?php

namespace App\Console\Commands;

use App\Models\ApprovalLevel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixApprovalLevels extends Command
{
    protected $signature = 'fix:approval-levels';
    protected $description = 'Fix approval levels by deleting problematic entries';

    public function handle()
    {
        $this->info('🔧 Fixing approval levels...');
        
        // Delete all levels with level_number = 0
        $zeroLevels = ApprovalLevel::where('level_number', 0)->get();
        $this->line("Found {$zeroLevels->count()} levels with level_number = 0");
        
        if ($zeroLevels->count() > 0) {
            foreach ($zeroLevels as $level) {
                $this->line("Deleting level 0 for workflow {$level->approval_workflow_id} (ID: {$level->id})");
            }
            
            $deleted = ApprovalLevel::where('level_number', 0)->delete();
            $this->info("✅ Deleted {$deleted} levels with level_number = 0");
        }
        
        // Also delete any levels with null level_number
        $nullLevels = ApprovalLevel::whereNull('level_number')->get();
        $this->line("Found {$nullLevels->count()} levels with null level_number");
        
        if ($nullLevels->count() > 0) {
            $deleted = ApprovalLevel::whereNull('level_number')->delete();
            $this->info("✅ Deleted {$deleted} levels with null level_number");
        }
        
        // Show remaining levels
        $remainingLevels = ApprovalLevel::count();
        $this->line("Remaining levels: {$remainingLevels}");
        
        $this->info('✅ Fix completed!');
    }
}

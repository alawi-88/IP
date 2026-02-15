<?php

namespace App\Console\Commands;

use App\Models\ApprovalLevel;
use Illuminate\Console\Command;

class DeleteZeroLevels extends Command
{
    protected $signature = 'delete:zero-levels';
    protected $description = 'Delete all approval levels with level_number = 0';

    public function handle()
    {
        $this->info('🗑️ Deleting all levels with level_number = 0...');
        
        $zeroLevels = ApprovalLevel::where('level_number', 0)->get();
        
        if ($zeroLevels->isEmpty()) {
            $this->info('✅ No levels with level_number = 0 found');
            return;
        }
        
        $this->line("Found {$zeroLevels->count()} levels with level_number = 0:");
        
        foreach ($zeroLevels as $level) {
            $this->line("  - Workflow ID: {$level->approval_workflow_id}, Level: {$level->level_number}, ID: {$level->id}");
        }
        
        $deletedCount = ApprovalLevel::where('level_number', 0)->delete();
        
        $this->info("✅ Deleted {$deletedCount} levels with level_number = 0");
    }
}
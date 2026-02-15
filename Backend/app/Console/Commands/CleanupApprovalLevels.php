<?php

namespace App\Console\Commands;

use App\Models\ApprovalLevel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupApprovalLevels extends Command
{
    protected $signature = 'cleanup:approval-levels';
    protected $description = 'Clean up problematic approval levels';

    public function handle()
    {
        $this->info('🧹 Cleaning up approval levels...');
        
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
        
        // Delete all levels with null level_number
        $nullLevels = ApprovalLevel::whereNull('level_number')->get();
        $this->line("Found {$nullLevels->count()} levels with null level_number");
        
        if ($nullLevels->count() > 0) {
            $deleted = ApprovalLevel::whereNull('level_number')->delete();
            $this->info("✅ Deleted {$deleted} levels with null level_number");
        }
        
        // Show remaining levels
        $remainingLevels = ApprovalLevel::count();
        $this->line("Remaining levels: {$remainingLevels}");
        
        // Show levels by workflow
        $levelsByWorkflow = ApprovalLevel::select('approval_workflow_id', DB::raw('count(*) as count'))
            ->groupBy('approval_workflow_id')
            ->get();
            
        $this->line("\nLevels by workflow:");
        foreach ($levelsByWorkflow as $workflow) {
            $this->line("Workflow {$workflow->approval_workflow_id}: {$workflow->count} levels");
        }
        
        $this->info('✅ Cleanup completed!');
    }
}
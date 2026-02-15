<?php

namespace App\Console\Commands;

use App\Models\ApprovalLevel;
use App\Models\ApprovalWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmergencyCleanupLevels extends Command
{
    protected $signature = 'emergency:cleanup-levels';
    protected $description = 'Emergency cleanup of all approval levels data';

    public function handle()
    {
        $this->info('🚨 EMERGENCY CLEANUP: Fixing all approval levels data...');
        $this->newLine();

        DB::transaction(function () {
            // Step 1: Delete ALL levels with level_number = 0
            $zeroLevels = ApprovalLevel::where('level_number', 0)->get();
            $this->line("Found {$zeroLevels->count()} levels with level_number = 0");
            
            foreach ($zeroLevels as $level) {
                $this->line("Deleting level 0 for workflow {$level->approval_workflow_id} (ID: {$level->id})");
            }
            
            $deletedZero = ApprovalLevel::where('level_number', 0)->delete();
            $this->info("✅ Deleted {$deletedZero} levels with level_number = 0");

            // Step 2: Delete ALL existing levels to start fresh
            $allLevels = ApprovalLevel::all();
            $this->line("Found {$allLevels->count()} total levels in database");
            
            $deletedAll = ApprovalLevel::query()->delete();
            $this->info("✅ Deleted ALL {$deletedAll} existing levels");

            // Step 3: Recreate all workflows with proper levels
            $workflows = ApprovalWorkflow::all();
            $this->line("Recreating levels for {$workflows->count()} workflows");
            
            foreach ($workflows as $workflow) {
                $this->line("\n--- Recreating Workflow {$workflow->id}: {$workflow->action} ---");
                
                $expectedLevels = (int) $workflow->levels;
                if ($expectedLevels < 1) {
                    $expectedLevels = 1;
                    $workflow->update(['levels' => 1]);
                }
                
                $this->line("Creating {$expectedLevels} levels for workflow {$workflow->id}");
                
                for ($i = 1; $i <= $expectedLevels; $i++) {
                    ApprovalLevel::create([
                        'approval_workflow_id' => $workflow->id,
                        'level_number' => $i,
                        'role_ids' => [],
                        'required_approvals' => 1,
                    ]);
                    $this->line("  ✅ Created level {$i}");
                }
            }
        });

        // Step 4: Final verification
        $this->newLine();
        $this->info('🔍 Final Verification:');
        
        $totalLevels = ApprovalLevel::count();
        $zeroLevels = ApprovalLevel::where('level_number', 0)->count();
        $duplicateLevels = ApprovalLevel::select('approval_workflow_id', 'level_number')
            ->groupBy('approval_workflow_id', 'level_number')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        $this->line("Total levels: {$totalLevels}");
        $this->line("Levels with number 0: {$zeroLevels}");
        $this->line("Duplicate level combinations: {$duplicateLevels}");
        
        if ($zeroLevels === 0 && $duplicateLevels === 0) {
            $this->info('✅ All issues fixed! Database is clean.');
        } else {
            $this->error('❌ Some issues remain');
        }

        $this->newLine();
        $this->info('✅ Emergency cleanup completed!');
    }
}

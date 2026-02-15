<?php

namespace App\Console\Commands;

use App\Models\ApprovalLevel;
use App\Models\ApprovalWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForceCleanupApprovalLevels extends Command
{
    protected $signature = 'force-cleanup:approval-levels';
    protected $description = 'Force cleanup all approval levels data issues';

    public function handle()
    {
        $this->info('🧹 Force Cleaning up ALL Approval Levels...');
        $this->newLine();

        DB::transaction(function () {
            // Step 1: Delete all levels with level_number = 0
            $zeroLevels = ApprovalLevel::where('level_number', 0)->get();
            $this->line("Found {$zeroLevels->count()} levels with level_number = 0");
            
            foreach ($zeroLevels as $level) {
                $this->line("Deleting level 0 for workflow {$level->approval_workflow_id}");
                $level->delete();
            }

            // Step 2: Find and fix duplicate levels
            $duplicates = ApprovalLevel::select('approval_workflow_id', 'level_number')
                ->groupBy('approval_workflow_id', 'level_number')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            $this->line("Found {$duplicates->count()} duplicate level combinations");

            foreach ($duplicates as $duplicate) {
                $levels = ApprovalLevel::where('approval_workflow_id', $duplicate->approval_workflow_id)
                    ->where('level_number', $duplicate->level_number)
                    ->orderBy('created_at')
                    ->get();

                // Keep the first one, delete the rest
                $keepLevel = $levels->first();
                $deleteLevels = $levels->skip(1);

                $this->line("Keeping level {$duplicate->level_number} for workflow {$duplicate->approval_workflow_id} (ID: {$keepLevel->id})");
                
                foreach ($deleteLevels as $deleteLevel) {
                    $this->line("Deleting duplicate level (ID: {$deleteLevel->id})");
                    $deleteLevel->delete();
                }
            }

            // Step 3: Clean up orphaned levels
            $orphanedLevels = ApprovalLevel::whereDoesntHave('approvalWorkflow')->get();
            $this->line("Found {$orphanedLevels->count()} orphaned levels");

            foreach ($orphanedLevels as $level) {
                $this->line("Deleting orphaned level (ID: {$level->id})");
                $level->delete();
            }

            // Step 4: Rebuild all workflows with proper level sequences
            $workflows = ApprovalWorkflow::with('approvalLevels')->get();
            $this->line("Processing {$workflows->count()} workflows");
            
            foreach ($workflows as $workflow) {
                $this->line("\n--- Processing Workflow {$workflow->id}: {$workflow->action} ---");
                
                // Delete all existing levels for this workflow
                $existingLevels = $workflow->approvalLevels;
                $this->line("Deleting {$existingLevels->count()} existing levels");
                $workflow->approvalLevels()->delete();
                
                // Recreate levels properly
                $expectedLevels = $workflow->levels;
                $this->line("Creating {$expectedLevels} new levels");
                
                for ($i = 1; $i <= $expectedLevels; $i++) {
                    ApprovalLevel::create([
                        'approval_workflow_id' => $workflow->id,
                        'level_number' => $i,
                        'role_ids' => [],
                        'required_approvals' => 1,
                    ]);
                    $this->line("  Created level {$i}");
                }
                
                // Verify the fix
                $newLevels = ApprovalLevel::where('approval_workflow_id', $workflow->id)
                    ->orderBy('level_number')
                    ->get();
                
                $levelNumbers = $newLevels->pluck('level_number')->toArray();
                $this->line("  Final levels: " . implode(', ', $levelNumbers));
            }
        });

        // Step 5: Final verification
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
            $this->info('✅ All issues fixed!');
        } else {
            $this->error('❌ Some issues remain');
        }

        $this->newLine();
        $this->info('✅ Force cleanup completed!');
    }
}

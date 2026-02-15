<?php

namespace App\Console\Commands;

use App\Models\ApprovalLevel;
use App\Models\ApprovalWorkflow;
use Illuminate\Console\Command;

class FixWorkflow5Levels extends Command
{
    protected $signature = 'fix:workflow-5-levels';
    protected $description = 'Fix specific workflow 5 level issues';

    public function handle()
    {
        $this->info('🔧 Fixing Workflow 5 Level Issues...');
        $this->newLine();

        $workflowId = 5;
        
        // Check if workflow exists
        $workflow = ApprovalWorkflow::find($workflowId);
        if (!$workflow) {
            $this->error("Workflow {$workflowId} not found");
            return;
        }
        
        $this->line("Found workflow: {$workflow->action} with {$workflow->levels} levels");
        
        // Get all levels for this workflow
        $levels = ApprovalLevel::where('approval_workflow_id', $workflowId)->get();
        $this->line("Current levels in database: {$levels->count()}");
        
        foreach ($levels as $level) {
            $this->line("  Level {$level->level_number}: role_ids = " . json_encode($level->role_ids));
        }
        
        // Delete all levels for this workflow
        $this->line("Deleting all levels for workflow {$workflowId}...");
        ApprovalLevel::where('approval_workflow_id', $workflowId)->delete();
        
        // Recreate levels properly
        $this->line("Recreating levels for workflow {$workflowId}...");
        for ($i = 1; $i <= $workflow->levels; $i++) {
            ApprovalLevel::create([
                'approval_workflow_id' => $workflowId,
                'level_number' => $i,
                'role_ids' => [],
                'required_approvals' => 1,
            ]);
            $this->line("  Created level {$i}");
        }
        
        // Verify the fix
        $newLevels = ApprovalLevel::where('approval_workflow_id', $workflowId)->get();
        $this->line("New levels count: {$newLevels->count()}");
        
        foreach ($newLevels as $level) {
            $this->line("  Level {$level->level_number}: role_ids = " . json_encode($level->role_ids));
        }
        
        $this->newLine();
        $this->info('✅ Workflow 5 levels fixed!');
    }
}

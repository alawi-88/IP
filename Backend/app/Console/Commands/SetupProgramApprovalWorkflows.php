<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use Spatie\Permission\Models\Role;

class SetupProgramApprovalWorkflows extends Command
{
    protected $signature = 'approval:setup-program-workflows';
    protected $description = 'Setup approval workflows for program actions (create, update, delete, archive)';

    public function handle()
    {
        $this->info('Setting up program approval workflows...');

        $actions = ['create', 'update', 'delete', 'archive'];
        $created = 0;

        foreach ($actions as $action) {
            $workflowAction = "program.{$action}";
            
            $workflow = ApprovalWorkflow::where('action', $workflowAction)->first();
            
            if (!$workflow) {
                $workflow = ApprovalWorkflow::create([
                    'action' => $workflowAction,
                    'is_active' => true,
                    'levels' => 2
                ]);
                
                $this->createApprovalLevels($workflow);
                $created++;
                $this->line("✓ Created workflow for: {$workflowAction}");
            } else {
                $this->line("✓ Workflow already exists for: {$workflowAction}");
            }
        }

        $this->info("Setup complete! Created {$created} new workflows.");
        return 0;
    }

    protected function createApprovalLevels(ApprovalWorkflow $workflow): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if ($adminRole) {
            ApprovalLevel::create([
                'approval_workflow_id' => $workflow->id,
                'level_number' => 1,
                'role_ids' => [$adminRole->id],
                'is_required' => true
            ]);
        }

        if ($superAdminRole) {
            ApprovalLevel::create([
                'approval_workflow_id' => $workflow->id,
                'level_number' => 2,
                'role_ids' => [$superAdminRole->id],
                'is_required' => true
            ]);
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalRequest;

class ExecuteApprovedActions extends Command
{
    protected $signature = 'approval:execute-approved';
    protected $description = 'Execute all approved actions that have not been executed yet';

    public function handle()
    {
        $this->info('Executing approved actions...');

        $approvedRequests = ApprovalRequest::where('status', 'approved')
            ->whereNull('executed_at')
            ->get();

        if ($approvedRequests->isEmpty()) {
            $this->info('No approved actions to execute.');
            return 0;
        }

        $executedCount = 0;
        $failedCount = 0;

        foreach ($approvedRequests as $request) {
            $this->line("Processing approval request ID: {$request->id} for action: {$request->action}");
            
            if ($request->executeAction()) {
                $request->update(['executed_at' => now()]);
                $executedCount++;
                $this->info("✓ Executed action for request ID: {$request->id}");
            } else {
                $failedCount++;
                $this->error("✗ Failed to execute action for request ID: {$request->id}");
            }
        }

        $this->info("Execution complete!");
        $this->info("Successfully executed: {$executedCount} actions");
        
        if ($failedCount > 0) {
            $this->warn("Failed to execute: {$failedCount} actions");
        }

        return 0;
    }
}

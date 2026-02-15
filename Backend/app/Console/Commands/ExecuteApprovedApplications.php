<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApplicationApprovalService;

class ExecuteApprovedApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'applications:execute-approved';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute approved application actions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $approvalService = new ApplicationApprovalService();
        $executedCount = $approvalService->executeApprovedActions();
        
        $this->info("Executed {$executedCount} approved application actions.");
        
        return Command::SUCCESS;
    }
}

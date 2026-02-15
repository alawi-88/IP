<?php

namespace App\Console\Commands;

use App\Services\SecureTokenService;
use Illuminate\Console\Command;

class CleanupExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired authentication tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting token cleanup...');
        
        $secureTokenService = app(SecureTokenService::class);
        
        // Get stats before cleanup
        $statsBefore = $secureTokenService->getTokenStats();
        $this->info("Tokens before cleanup: {$statsBefore['total']} total, {$statsBefore['active']} active, {$statsBefore['expired']} expired");
        
        if ($statsBefore['expired'] === 0) {
            $this->info('No expired tokens found. Nothing to clean up.');
            return 0;
        }
        
        if (!$this->option('force')) {
            if (!$this->confirm("Are you sure you want to delete {$statsBefore['expired']} expired tokens?")) {
                $this->info('Token cleanup cancelled.');
                return 0;
            }
        }
        
        // Perform cleanup
        $deletedCount = $secureTokenService->cleanupExpiredTokens();
        
        // Get stats after cleanup
        $statsAfter = $secureTokenService->getTokenStats();
        
        $this->info("Token cleanup completed successfully!");
        $this->info("Deleted: {$deletedCount} expired tokens");
        $this->info("Tokens after cleanup: {$statsAfter['total']} total, {$statsAfter['active']} active, {$statsAfter['expired']} expired");
        
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\SecureTokenService;
use Illuminate\Console\Command;

class TokenStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display authentication token statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $secureTokenService = app(SecureTokenService::class);
        $stats = $secureTokenService->getTokenStats();
        
        $this->info('Authentication Token Statistics');
        $this->info('================================');
        $this->info("Total tokens: {$stats['total']}");
        $this->info("Active tokens: {$stats['active']}");
        $this->info("Expired tokens: {$stats['expired']}");
        
        if ($stats['expired'] > 0) {
            $this->warn("⚠️  {$stats['expired']} expired tokens found. Consider running 'php artisan tokens:cleanup' to remove them.");
        }
        
        $this->info('');
        $this->info('Security Recommendations:');
        $this->info('• Monitor token usage patterns');
        $this->info('• Regularly clean up expired tokens');
        $this->info('• Review token creation logs for suspicious activity');
        $this->info('• Consider implementing token rotation policies');
        
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\PersonalAccessToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SecurityMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:monitor {--hours=24 : Number of hours to look back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor security events and suspicious activities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $since = now()->subHours($hours);
        
        $this->info("Security Monitor - Last {$hours} hours");
        $this->info('=====================================');
        
        // Check for suspicious token activities
        $this->checkSuspiciousTokenActivities($since);
        
        // Check for multiple IPs per user
        $this->checkMultipleIPsPerUser($since);
        
        // Check for unusual token creation patterns
        $this->checkUnusualTokenCreation($since);
        
        // Check for expired tokens still being used
        $this->checkExpiredTokenUsage($since);
        
        $this->info('');
        $this->info('Security monitoring completed.');
        
        return 0;
    }
    
    private function checkSuspiciousTokenActivities($since)
    {
        $this->info("\n🔍 Checking for suspicious token activities...");
        
        // Tokens created from multiple IPs
        $multiIPTokens = PersonalAccessToken::where('created_at', '>=', $since)
            ->select('tokenable_id', 'tokenable_type', DB::raw('COUNT(DISTINCT created_from_ip) as ip_count'))
            ->groupBy('tokenable_id', 'tokenable_type')
            ->having('ip_count', '>', 3)
            ->get();
            
        if ($multiIPTokens->count() > 0) {
            $this->warn("⚠️  Found {$multiIPTokens->count()} users with tokens created from multiple IPs");
            foreach ($multiIPTokens as $token) {
                $this->line("   User {$token->tokenable_id} ({$token->tokenable_type}): {$token->ip_count} different IPs");
            }
        } else {
            $this->info("✅ No suspicious multi-IP token creation detected");
        }
    }
    
    private function checkMultipleIPsPerUser($since)
    {
        $this->info("\n🌐 Checking for multiple IPs per user...");
        
        $multiIPUsers = PersonalAccessToken::where('last_used_at', '>=', $since)
            ->select('tokenable_id', 'tokenable_type', DB::raw('COUNT(DISTINCT last_used_from_ip) as ip_count'))
            ->groupBy('tokenable_id', 'tokenable_type')
            ->having('ip_count', '>', 5)
            ->get();
            
        if ($multiIPUsers->count() > 0) {
            $this->warn("⚠️  Found {$multiIPUsers->count()} users accessing from multiple IPs");
            foreach ($multiIPUsers as $user) {
                $this->line("   User {$user->tokenable_id} ({$user->tokenable_type}): {$user->ip_count} different IPs");
            }
        } else {
            $this->info("✅ No unusual multi-IP access patterns detected");
        }
    }
    
    private function checkUnusualTokenCreation($since)
    {
        $this->info("\n📊 Checking for unusual token creation patterns...");
        
        // Users creating many tokens
        $highTokenUsers = PersonalAccessToken::where('created_at', '>=', $since)
            ->select('tokenable_id', 'tokenable_type', DB::raw('COUNT(*) as token_count'))
            ->groupBy('tokenable_id', 'tokenable_type')
            ->having('token_count', '>', 10)
            ->get();
            
        if ($highTokenUsers->count() > 0) {
            $this->warn("⚠️  Found {$highTokenUsers->count()} users creating many tokens");
            foreach ($highTokenUsers as $user) {
                $this->line("   User {$user->tokenable_id} ({$user->tokenable_type}): {$user->token_count} tokens");
            }
        } else {
            $this->info("✅ No unusual token creation patterns detected");
        }
    }
    
    private function checkExpiredTokenUsage($since)
    {
        $this->info("\n⏰ Checking for expired token usage...");
        
        $expiredUsage = PersonalAccessToken::where('expires_at', '<', now())
            ->where('last_used_at', '>=', $since)
            ->count();
            
        if ($expiredUsage > 0) {
            $this->error("🚨 Found {$expiredUsage} expired tokens still being used!");
        } else {
            $this->info("✅ No expired token usage detected");
        }
    }
}

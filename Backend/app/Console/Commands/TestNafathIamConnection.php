<?php

namespace App\Console\Commands;

use App\Services\NafathIamService;
use Illuminate\Console\Command;

class TestNafathIamConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nafath:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Nafath IAM connection and get access token';

    /**
     * Execute the console command.
     */
    public function handle(NafathIamService $nafathIamService): int
    {
        $this->info('Testing Nafath IAM connection...');
        
        $result = $nafathIamService->testConnection();
        
        if ($result['success']) {
            $this->info('✅ Connection successful!');
            $this->line('Token: ' . $result['token']);
            $this->line('Timestamp: ' . $result['timestamp']);
            return 0;
        } else {
            $this->error('❌ Connection failed!');
            $this->line('Error: ' . $result['token']);
            $this->line('Timestamp: ' . $result['timestamp']);
            return 1;
        }
    }
}

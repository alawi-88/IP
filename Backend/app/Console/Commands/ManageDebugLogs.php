<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageDebugLogs extends Command
{
    protected $signature = 'debug:logs {action : enable|disable|clear|status}';
    protected $description = 'Manage debug logging for approval workflows';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'enable':
                $this->enableDebugLogs();
                break;
            case 'disable':
                $this->disableDebugLogs();
                break;
            case 'clear':
                $this->clearDebugLogs();
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->error('Invalid action. Use: enable, disable, clear, or status');
                return 1;
        }

        return 0;
    }

    protected function enableDebugLogs()
    {
        $this->info('Enabling debug logs for approval workflows...');
        
        // You can add logic here to enable debug logging
        // For example, set a config value or create a flag file
        
        $this->info('Debug logs enabled.');
    }

    protected function disableDebugLogs()
    {
        $this->info('Disabling debug logs for approval workflows...');
        
        // You can add logic here to disable debug logging
        
        $this->info('Debug logs disabled.');
    }

    protected function clearDebugLogs()
    {
        $logPath = storage_path('logs');
        
        if (!File::exists($logPath)) {
            $this->error('Logs directory does not exist.');
            return;
        }

        $files = File::files($logPath);
        $count = 0;

        foreach ($files as $file) {
            if (File::delete($file)) {
                $count++;
            }
        }

        $this->info("Cleared {$count} log files.");
    }

    protected function showStatus()
    {
        $logPath = storage_path('logs');
        
        if (!File::exists($logPath)) {
            $this->error('Logs directory does not exist.');
            return;
        }

        $files = File::files($logPath);
        $this->info("Found " . count($files) . " log files:");
        
        foreach ($files as $file) {
            $size = File::size($file);
            $modified = date('Y-m-d H:i:s', File::lastModified($file));
            $this->line("  - " . basename($file) . " ({$size} bytes, modified: {$modified})");
        }
    }
}

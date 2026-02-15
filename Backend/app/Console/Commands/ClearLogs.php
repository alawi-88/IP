<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogs extends Command
{
    protected $signature = 'logs:clear';
    protected $description = 'Clear all log files';

    public function handle()
    {
        $logPath = storage_path('logs');
        
        if (!File::exists($logPath)) {
            $this->error('Logs directory does not exist.');
            return 1;
        }

        $files = File::files($logPath);
        $count = 0;

        foreach ($files as $file) {
            if (File::delete($file)) {
                $count++;
            }
        }

        $this->info("Cleared {$count} log files.");
        return 0;
    }
}

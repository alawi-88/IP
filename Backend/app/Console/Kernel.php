<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    
    protected function schedule(Schedule $schedule): void
    {
        // Automatically mark expired sessions as no_show
        $schedule->command('sessions:mark-expired-as-no-show')
            ->everyThirtyMinutes()
            ->timezone('Asia/Riyadh')
            ->description('Automatically mark sessions as no_show when their end time has passed');

        // Send mentorship session reminders 60 minutes before start time
        $schedule->command('sessions:send-reminders --hours=1')
            ->everyFiveMinutes()
            ->timezone('Asia/Riyadh')
            ->description('Send email and in-platform reminders 60 minutes before mentorship sessions start')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}


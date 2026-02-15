<?php

namespace App\Console\Commands;

use App\Models\MentorSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkExpiredSessionsAsNoShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:mark-expired-as-no-show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically mark sessions as no_show when their end time has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired sessions...');

        // Find sessions that:
        // 1. Have status 'scheduled' or 'confirmed'
        // 2. Have a scheduled_at date
        // 3. The session end time (scheduled_at + duration_minutes) has passed
        $expiredSessions = MentorSession::whereIn('status', ['scheduled', 'confirmed'])
            ->whereNotNull('scheduled_at')
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 30) MINUTE) < NOW()')
            ->get();

        $updatedCount = 0;

        foreach ($expiredSessions as $session) {
            try {
                // Store the original status before updating
                $previousStatus = $session->status;

                // Calculate the end time for logging
                $endTime = $session->scheduled_at->copy()->addMinutes($session->duration_minutes ?? 30);

                // Update session status to no_show
                $session->update([
                    'status' => 'no_show',
                    'ended_at' => $endTime,
                ]);

                // Log the status change
                activity('mentor_session')
                    ->performedOn($session)
                    ->event('session_auto_marked_no_show')
                    ->withProperties([
                        'session_id' => $session->id,
                        'scheduled_at' => $session->scheduled_at->format('Y-m-d H:i:s'),
                        'end_time' => $endTime->format('Y-m-d H:i:s'),
                        'previous_status' => $previousStatus,
                        'new_status' => 'no_show',
                        'mentor_id' => $session->mentor_id,
                        'participant_id' => $session->participant_id,
                    ])
                    ->log('Session automatically marked as no_show after end time passed');

                $updatedCount++;

            } catch (\Exception $e) {
                Log::error("Failed to mark session as no_show", [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to update session {$session->id}: {$e->getMessage()}");
            }
        }

        if ($updatedCount > 0) {
            $this->info("Successfully marked {$updatedCount} session(s) as no_show.");
        } else {
            $this->info("No expired sessions found.");
        }

        return Command::SUCCESS;
    }
}


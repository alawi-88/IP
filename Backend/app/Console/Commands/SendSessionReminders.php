<?php

namespace App\Console\Commands;

use App\Models\MentorSession;
use App\Notifications\Mentor\SessionReminderNotification as MentorSessionReminderNotification;
use App\Notifications\Participant\SessionReminderNotification as ParticipantSessionReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSessionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:send-reminders 
                            {--hours=24 : Number of hours before session to send reminder}
                            {--sync : Send notifications synchronously instead of queuing}
                            {--list : List upcoming sessions without sending reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for upcoming mentor sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursBeforeSession = (int) $this->option('hours');
        $minutesBeforeSession = $hoursBeforeSession * 60;
        
        // Calculate the target time: sessions scheduled exactly X hours from now
        $now = Carbon::now();
        $targetTime = $now->copy()->addMinutes($minutesBeforeSession);
        
        // Create a window: 5 minutes before and 5 minutes after the target time
        // This ensures we catch sessions even if the cron runs slightly early or late
        $windowStart = $targetTime->copy()->subMinutes(5);
        $windowEnd = $targetTime->copy()->addMinutes(5);
        
        $this->info("Current time: {$now->format('Y-m-d H:i:s')}");
        $this->info("Target time (sessions scheduled {$hoursBeforeSession} hour(s) from now): {$targetTime->format('Y-m-d H:i:s')}");
        $this->info("Looking for sessions scheduled between {$windowStart->format('Y-m-d H:i:s')} and {$windowEnd->format('Y-m-d H:i:s')}");

        // Find sessions scheduled within the reminder window
        $sessions = MentorSession::whereIn('status', ['scheduled', 'confirmed'])
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->whereNotNull('participant_id')
            ->whereNotNull('scheduled_at')
            ->whereNotNull('mentor_id')
            ->with(['mentor', 'participant'])
            ->get();

        $this->info("Found {$sessions->count()} session(s) in the reminder window");

        // If --list option is set, just display sessions and exit
        if ($this->option('list')) {
            if ($sessions->isEmpty()) {
                $this->warn("No sessions found in the reminder window.");
                $this->info("\nUpcoming sessions (next 24 hours):");
                $upcomingSessions = MentorSession::whereIn('status', ['scheduled', 'confirmed'])
                    ->where('scheduled_at', '>', $now)
                    ->where('scheduled_at', '<=', $now->copy()->addHours(24))
                    ->whereNotNull('participant_id')
                    ->whereNotNull('scheduled_at')
                    ->whereNotNull('mentor_id')
                    ->with(['mentor', 'participant'])
                    ->orderBy('scheduled_at', 'asc')
                    ->get();
                
                if ($upcomingSessions->isEmpty()) {
                    $this->warn("No upcoming sessions found in the next 24 hours.");
                } else {
                    $this->table(
                        ['ID', 'Scheduled At', 'Mentor', 'Participant', 'Status', 'Minutes From Now'],
                        $upcomingSessions->map(function ($session) use ($now) {
                            $minutesFromNow = $session->scheduled_at->diffInMinutes($now);
                            $mentorName = is_array($session->mentor->name ?? null) 
                                ? ($session->mentor->name['en'] ?? 'N/A')
                                : ($session->mentor->name ?? 'N/A');
                            $participantName = is_array($session->participant->name ?? null)
                                ? ($session->participant->name['en'] ?? 'N/A')
                                : ($session->participant->name ?? 'N/A');
                            
                            return [
                                $session->id,
                                $session->scheduled_at->format('Y-m-d H:i:s'),
                                $mentorName,
                                $participantName,
                                $session->status,
                                $minutesFromNow,
                            ];
                        })->toArray()
                    );
                }
            } else {
                $this->table(
                    ['ID', 'Scheduled At', 'Mentor', 'Participant', 'Status', 'Time Diff (min)'],
                    $sessions->map(function ($session) use ($targetTime) {
                        $timeDiff = abs($session->scheduled_at->diffInMinutes($targetTime));
                        $mentorName = is_array($session->mentor->name ?? null) 
                            ? ($session->mentor->name['en'] ?? 'N/A')
                            : ($session->mentor->name ?? 'N/A');
                        $participantName = is_array($session->participant->name ?? null)
                            ? ($session->participant->name['en'] ?? 'N/A')
                            : ($session->participant->name ?? 'N/A');
                        
                        return [
                            $session->id,
                            $session->scheduled_at->format('Y-m-d H:i:s'),
                            $mentorName,
                            $participantName,
                            $session->status,
                            $timeDiff,
                        ];
                    })->toArray()
                );
            }
            return Command::SUCCESS;
        }

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($sessions as $session) {
            try {
                // Double-check: session should be scheduled approximately at the target time
                $timeDiff = abs($session->scheduled_at->diffInMinutes($targetTime));
                
                if ($timeDiff > 10) {
                    // Skip if too far from target time (more than 10 minutes)
                    $skippedCount++;
                    $this->warn("Skipping session {$session->id}: scheduled at {$session->scheduled_at->format('Y-m-d H:i:s')}, difference: {$timeDiff} minutes");
                    continue;
                }

                // Check if reminders were already sent (prevent duplicate reminders)
                // We'll check if a reminder notification exists for this session in the last 2 hours
                $recentReminderParticipant = \Illuminate\Notifications\DatabaseNotification::where('notifiable_type', 'App\Models\Participant')
                    ->where('notifiable_id', $session->participant_id)
                    ->where('type', 'App\Notifications\Participant\SessionReminderNotification')
                    ->where('created_at', '>=', $now->copy()->subHours(2))
                    ->whereJsonContains('data->session_id', $session->id)
                    ->exists();

                $recentReminderMentor = \Illuminate\Notifications\DatabaseNotification::where('notifiable_type', 'App\Models\Mentor')
                    ->where('notifiable_id', $session->mentor_id)
                    ->where('type', 'App\Notifications\Mentor\SessionReminderNotification')
                    ->where('created_at', '>=', $now->copy()->subHours(2))
                    ->whereJsonContains('data->session_id', $session->id)
                    ->exists();

                if ($recentReminderParticipant || $recentReminderMentor) {
                    $this->warn("Skipping session {$session->id}: reminder already sent in the last 2 hours");
                    $skippedCount++;
                    continue;
                }

                // Validate session has required relationships
                if (!$session->mentor) {
                    $this->warn("Session {$session->id} has no mentor (mentor_id: {$session->mentor_id})");
                    continue;
                }

                if (!$session->participant) {
                    $this->warn("Session {$session->id} has no participant (participant_id: {$session->participant_id})");
                    continue;
                }

                // Validate email addresses
                if (empty($session->mentor->email)) {
                    $this->warn("Mentor {$session->mentor_id} has no email address");
                }

                if (empty($session->participant->email)) {
                    $this->warn("Participant {$session->participant_id} has no email address");
                }

                // Send reminder to mentor
                $notification = new MentorSessionReminderNotification($session);
                
                // If --sync flag is set, send synchronously
                if ($this->option('sync')) {
                    $session->mentor->notifyNow($notification);
                    $this->info("✓ Sent reminder synchronously to mentor {$session->mentor_id} ({$session->mentor->email}) for session {$session->id}");
                } else {
                    $session->mentor->notify($notification);
                    $this->info("✓ Queued reminder for mentor {$session->mentor_id} ({$session->mentor->email}) for session {$session->id}");
                }

                // Send reminder to participant
                $notification = new ParticipantSessionReminderNotification($session);
                
                // If --sync flag is set, send synchronously
                if ($this->option('sync')) {
                    $session->participant->notifyNow($notification);
                    $this->info("✓ Sent reminder synchronously to participant {$session->participant_id} ({$session->participant->email}) for session {$session->id}");
                } else {
                    $session->participant->notify($notification);
                    $this->info("✓ Queued reminder for participant {$session->participant_id} ({$session->participant->email}) for session {$session->id}");
                }

                $sentCount++;

            } catch (\Exception $e) {
                Log::error("Failed to send session reminder", [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Failed to send reminder for session {$session->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sentCount} session reminder(s), skipped {$skippedCount} session(s) for sessions scheduled {$hoursBeforeSession} hour(s) from now.");

        return Command::SUCCESS;
    }
}


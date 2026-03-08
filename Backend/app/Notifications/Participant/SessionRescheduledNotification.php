<?php

namespace App\Notifications\Participant;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionRescheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected MentorSession $session;
    protected array $changes;

    public function __construct(MentorSession $session, array $changes = [])
    {
        $this->session = $session;
        $this->changes = $changes;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        // Reload session from database to ensure all data is loaded (important for queued notifications)
        $this->session = $this->session->fresh(['mentor', 'participant', 'program']);
        
        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $participantName = is_array($notifiable->name ?? null)
            ? ($notifiable->name[$locale] ?? $notifiable->name['en'] ?? '')
            : ($notifiable->name ?? '');

        $mentorName = '';
        if ($this->session->mentor) {
            $mentorName = is_array($this->session->mentor->name ?? null) 
                ? ($this->session->mentor->name[$locale] ?? $this->session->mentor->name['en'] ?? '') 
                : ($this->session->mentor->name ?? '');
        }

        $message = (new MailMessage)
            ->theme($locale === 'ar' ? 'ar-default' : 'default')
            ->subject(__('notifications.session_rescheduled_subject'))
           // ->salutation(' ')
            ->greeting(__('notifications.hello', ['name' => $participantName]))
            ->line(__('notifications.session_rescheduled_message_new', [
                'new_date' => $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y'),
                'new_time' => $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A'),
            ]));

        // Show mentor information
        if ($mentorName) {
            $message->line(__('notifications.mentor_information'))
                    ->line(__('notifications.full_name_label') . ': ' . $mentorName);
        }

        // Show what changed with old and new values
        if (isset($this->changes['scheduled_at'])) {
            $oldDate = '';
            // Translate "at" based on locale. In Arabic, use "في" instead of "at".
            $atStr = $locale === 'ar' ? 'الساعة' : 'at';
            $oldDate2 = $this->changes['scheduled_at']['old']->locale($locale)->translatedFormat("M d, Y $atStr g:i A");
            if (is_array($this->changes['scheduled_at']) && isset($this->changes['scheduled_at']['old']) && $this->changes['scheduled_at']['old'] !== null) {
                try {
                    $oldValue = $this->changes['scheduled_at']['old'];
                    $parsedDate = $oldValue instanceof \Carbon\Carbon ? $oldValue : \Carbon\Carbon::parse($oldValue);
                    if ($parsedDate !== null && $parsedDate instanceof \Carbon\Carbon) {
                        $localizedDate = $parsedDate->setLocale($locale);
                        if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                            $oldDate = $localizedDate->format('M d, Y \a\t g:i A');
                        }
                    }
                } catch (\Exception $e) {
                    $oldDate = '';
                }
            }
            $newDate = 'N/A';
            // Translate "at" based on locale. In Arabic, use "في" instead of "at".
            $atStr = $locale === 'ar' ? 'الساعة' : 'at';
            $newDate2 = $this->session->scheduled_at->locale($locale)->translatedFormat("M d, Y $atStr g:i A");
            // Try to get scheduled_at from changes first (most reliable), then from session
            $scheduledAt = null;
            if (isset($this->changes['scheduled_at']['new'])) {
                $newValue = $this->changes['scheduled_at']['new'];
                try {
                    $scheduledAt = $newValue instanceof \Carbon\Carbon ? $newValue : \Carbon\Carbon::parse($newValue);
                } catch (\Exception $e) {
                    $scheduledAt = null;
                }
            }
            // Fallback to session scheduled_at if not in changes
            if (!$scheduledAt && $this->session->scheduled_at) {
                $scheduledAt = $this->session->scheduled_at;
            }
            if ($scheduledAt) {
                try {
                    $localizedDate = $scheduledAt instanceof \Carbon\Carbon ? $scheduledAt->setLocale($locale) : \Carbon\Carbon::parse($scheduledAt)->setLocale($locale);
                    if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                        $newDate = $localizedDate->format("M d, Y $atStr g:i A");
                    }
                } catch (\Exception $e) {
                    $newDate = 'N/A';
                }
            }
            
            if ($oldDate2) {
                $message->line(__('notifications.previous_time') . ': ' . $oldDate2)
                        ->line(__('notifications.new_time') . ': ' . $newDate2);
            } else {
                $message->line(__('notifications.session_time_label') . ': ' . $newDate2);
            }
        }

        if (isset($this->changes['duration_minutes'])) {
            $oldDuration = is_array($this->changes['duration_minutes']) && isset($this->changes['duration_minutes']['old'])
                ? $this->formatDuration($this->changes['duration_minutes']['old'])
                : '';
            $newDuration = $this->session->duration_formatted;
            
            if ($oldDuration) {
                $message->line(__('notifications.previous_duration') . ': ' . $oldDuration)
                        ->line(__('notifications.new_duration') . ': ' . $newDuration);
            } else {
                $message->line(__('notifications.session_duration_label') . ': ' . $newDuration);
            }
        }

        // Show current session details
        $message->line(__('notifications.session_details'));
        
        // Try to get scheduled_at from changes first (most reliable), then from session
        $scheduledAt = null;
        if (isset($this->changes['scheduled_at']['new'])) {
            $newValue = $this->changes['scheduled_at']['new'];
            try {
                $scheduledAt = $newValue instanceof \Carbon\Carbon ? $newValue : \Carbon\Carbon::parse($newValue);
            } catch (\Exception $e) {
                $scheduledAt = null;
            }
        }
        // Fallback to session scheduled_at if not in changes
        if (!$scheduledAt && $this->session->scheduled_at) {
            $scheduledAt = $this->session->scheduled_at;
        }
        
        if ($scheduledAt) {
            try {
                $localizedDate = $scheduledAt instanceof \Carbon\Carbon ? $scheduledAt->setLocale($locale) : \Carbon\Carbon::parse($scheduledAt)->setLocale($locale);
                if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                    $message->line(__('notifications.session_date_label') . ': ' . $localizedDate->format('M d, Y'))
                            ->line(__('notifications.session_time_label') . ': ' . $localizedDate->format('g:i A'));
                }
            } catch (\Exception $e) {
                // Skip date/time display if there's an error
            }
        }
        
        $message->line(__('notifications.session_duration_label') . ': ' . $this->session->duration_formatted);

        if ($this->session->join_url) {
            $message->action(
                __('notifications.join_session'),
                $this->session->join_url
            );
        }

        // Display note/description if available
        if ($this->session->description) {
            $message->line(__('notifications.note_label'))
                ->line($this->session->description);
        }

        $message->line(__('notifications.session_rescheduled_footer'));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        // Reload session from database to ensure all data is loaded (important for queued notifications)
        $this->session = $this->session->fresh(['mentor', 'participant', 'program']);
        
        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $mentorName = '';
        if ($this->session->mentor) {
            $mentorName = is_array($this->session->mentor->name ?? null)
                ? ($this->session->mentor->name[$locale] ?? $this->session->mentor->name['en'] ?? '')
                : ($this->session->mentor->name ?? '');
        }

        // Build detailed message with old and new values
        $messageParts = [__('notifications.session_rescheduled_message', [
            'title' => $this->session->title ?? '',
            'new_date' => $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y'),
            'new_time' => $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A'),
        ])];
        
        if (isset($this->changes['scheduled_at'])) {
            $oldDate = '';
            // Translate "at" based on locale. In Arabic, use "في" instead of "at".
            $atStr = $locale === 'ar' ? 'الساعة' : 'at';
            $oldDate2 = $this->changes['scheduled_at']['old']->locale($locale)->translatedFormat("M d, Y $atStr g:i A");
            if (is_array($this->changes['scheduled_at']) && isset($this->changes['scheduled_at']['old']) && $this->changes['scheduled_at']['old'] !== null) {
                try {
                    $oldValue = $this->changes['scheduled_at']['old'];
                    $parsedDate = $oldValue instanceof \Carbon\Carbon ? $oldValue : \Carbon\Carbon::parse($oldValue);
                    if ($parsedDate !== null && $parsedDate instanceof \Carbon\Carbon) {
                        $localizedDate = $parsedDate->setLocale($locale);
                        if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                            $oldDate = $localizedDate->format("M d, Y $atStr g:i A");
                        }
                    }
                } catch (\Exception $e) {
                    $oldDate = '';
                }
            }
            $newDate = 'N/A';
            $newDate2 = $this->session->scheduled_at->locale($locale)->translatedFormat("M d, Y $atStr g:i A");
            // Try to get scheduled_at from changes first (most reliable), then from session
            $scheduledAt = null;
            if (isset($this->changes['scheduled_at']['new'])) {
                $newValue = $this->changes['scheduled_at']['new'];
                try {
                    $scheduledAt = $newValue instanceof \Carbon\Carbon ? $newValue : \Carbon\Carbon::parse($newValue);
                } catch (\Exception $e) {
                    $scheduledAt = null;
                }
            }
            // Fallback to session scheduled_at if not in changes
            if (!$scheduledAt && $this->session->scheduled_at) {
                $scheduledAt = $this->session->scheduled_at;
            }
            if ($scheduledAt) {
                try {
                    $localizedDate = $scheduledAt instanceof \Carbon\Carbon ? $scheduledAt->setLocale($locale) : \Carbon\Carbon::parse($scheduledAt)->setLocale($locale);
                    if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                        $newDate = $localizedDate->format("M d, Y $atStr g:i A");
                    }
                } catch (\Exception $e) {
                    $newDate = 'N/A';
                }
            }
            
            if ($oldDate2) {
                $messageParts[] = __('notifications.previous_time') . ': ' . $oldDate2;
            }
            $messageParts[] = __('notifications.new_time') . ': ' . $newDate2;
        }
        
        if (isset($this->changes['duration_minutes'])) {
            $oldDuration = is_array($this->changes['duration_minutes']) && isset($this->changes['duration_minutes']['old'])
                ? $this->formatDuration($this->changes['duration_minutes']['old'])
                : '';
            $newDuration = $this->session->duration_formatted;
            
            if ($oldDuration) {
                $messageParts[] = __('notifications.previous_duration') . ': ' . $oldDuration;
            }
            $messageParts[] = __('notifications.new_duration') . ': ' . $newDuration;
        }

        return [
            'type' => 'session_rescheduled',
            'session_id' => $this->session->id,
            'title' => $this->session->title,
            'scheduled_at' => $this->session->scheduled_at ? $this->session->scheduled_at->format('Y-m-d H:i:s') : null,
            'duration_minutes' => $this->session->duration_minutes,
            'join_url' => $this->session->join_url,
            'video_tool' => $this->session->video_tool,
            'mentor_name' => $mentorName,
            'changes' => $this->changes,
            'message' => implode("\n", $messageParts),
        ];
    }

    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
        }

        return "{$remainingMinutes}m";
    }
}


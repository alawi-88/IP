<?php

namespace App\Notifications\Participant;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected MentorSession $session;

    public function __construct(MentorSession $session)
    {
        $this->session = $session;
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

        $mentorName = '';
        if ($this->session->mentor) {
            $mentorName = is_array($this->session->mentor->name ?? null) 
                ? ($this->session->mentor->name[$locale] ?? $this->session->mentor->name['en'] ?? '') 
                : ($this->session->mentor->name ?? '');
        }

        $participantName = is_array($notifiable->name ?? null)
            ? ($notifiable->name[$locale] ?? $notifiable->name['en'] ?? '')
            : ($notifiable->name ?? '');

        $scheduledDate = 'N/A';
        $scheduledDate2 = $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y');
        $scheduledTime = 'N/A';
        $scheduledTime2 = $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A');
        if ($this->session->scheduled_at) {
            try {
                $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon 
                    ? $this->session->scheduled_at 
                    : \Carbon\Carbon::parse($this->session->scheduled_at);
                
                if ($scheduledAt) {
                    $localizedDate = $scheduledAt->setLocale($locale);
                    if ($localizedDate) {
                        $scheduledDate = $localizedDate->format('M d, Y');
                        $scheduledTime = $localizedDate->format('g:i A');
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to format scheduled_at in SessionAcceptedNotification::toMail", [
                    'session_id' => $this->session->id,
                    'scheduled_at' => $this->session->scheduled_at,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = (new MailMessage)
            ->theme($locale === 'ar' ? 'ar-default' : 'default')
            ->subject(__('notifications.session_accepted_subject'))
           // ->salutation(' ')
            ->greeting(__('notifications.hello', ['name' => $participantName]))
            ->line(__('notifications.session_accepted_message', [
                'mentor_name' => $mentorName,
               // 'title' => $this->session->title ?? '',
                'date' => $scheduledDate2,
                'time' => $scheduledTime2,
                'duration' => $this->session->duration_formatted ?? 'N/A',
            ]));

        if ($this->session->join_url) {
            $message->action(
                __('notifications.join_session'),
                $this->session->join_url
            );
        }

        if ($this->session->description) {
            $message->line(__('notifications.session_description'))
                ->line($this->session->description);
        }

        $message->line(__('notifications.session_accepted_footer'));

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

        $scheduledDate = 'N/A';
        $scheduledDate2 = $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y');
        $scheduledTime = 'N/A';
        $scheduledTime2 = $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A');
        if ($this->session->scheduled_at) {
            try {
                $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon 
                    ? $this->session->scheduled_at 
                    : \Carbon\Carbon::parse($this->session->scheduled_at);
                
                if ($scheduledAt) {
                    $localizedDate = $scheduledAt->setLocale($locale);
                    if ($localizedDate) {
                        $scheduledDate = $localizedDate->format('M d, Y');
                        $scheduledTime = $localizedDate->format('g:i A');
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to format scheduled_at in SessionAcceptedNotification::toArray", [
                    'session_id' => $this->session->id,
                    'scheduled_at' => $this->session->scheduled_at,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $scheduledAtFormatted = null;
        if ($this->session->scheduled_at) {
            try {
                $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon 
                    ? $this->session->scheduled_at 
                    : \Carbon\Carbon::parse($this->session->scheduled_at);
                
                if ($scheduledAt) {
                    $scheduledAtFormatted = $scheduledAt->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to format scheduled_at in SessionAcceptedNotification::toArray", [
                    'session_id' => $this->session->id,
                    'scheduled_at' => $this->session->scheduled_at,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Build a formatted message for the title
        $formattedMessage = __('notifications.session_accepted_message', [
            'mentor_name' => $mentorName,
            //'title' => $this->session->title ?? '',
            'date' => $scheduledDate2,
            'time' => $scheduledTime2,
            'duration' => $this->session->duration_formatted ?? 'N/A',
        ]);

        return [
            'type' => 'session_accepted',
            'session_id' => $this->session->id,
            'title' => $formattedMessage, // Use formatted message as title
            'scheduled_at' => $scheduledAtFormatted,
            'duration_minutes' => $this->session->duration_minutes,
            'join_url' => $this->session->join_url,
            'video_tool' => $this->session->video_tool,
            'mentor_name' => $mentorName,
            'message' => $formattedMessage, // Keep for backward compatibility
        ];
    }
}


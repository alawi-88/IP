<?php

namespace App\Notifications\Participant;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected MentorSession $session;
    
    /**
     * The locale for the notification.
     * This property is set when the notification is created to preserve locale for queued notifications.
     *
     * @var string|null
     */
    public $locale = null;

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
        $this->session = $this->session->fresh(['mentor', 'participant', 'competition']);

        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $mentorName = '';
        if ($this->session->mentor) {
            $mentorName = is_array($this->session->mentor->name ?? null) 
                ? ($this->session->mentor->name[$locale] ?? $this->session->mentor->name['en'] ?? '') 
                : ($this->session->mentor->name ?? '');
        }

        $mentorEmail = $this->session->mentor->email ?? '';

        $competitionTitle = '';
        if ($this->session->relationLoaded('competition') && $this->session->competition) {
            $competitionTitle = is_array($this->session->competition->title ?? null)
                ? ($this->session->competition->title[$locale] ?? $this->session->competition->title['en'] ?? '')
                : ($this->session->competition->title ?? '');
        } elseif (!$this->session->relationLoaded('competition') && $this->session->competition_id) {
            // Load competition if not already loaded
            $competition = \App\Models\Competition::find($this->session->competition_id);
            if ($competition) {
                $competitionTitle = is_array($competition->title ?? null)
                    ? ($competition->title[$locale] ?? $competition->title['en'] ?? '')
                    : ($competition->title ?? '');
            }
        }

        $participantName = is_array($notifiable->name ?? null)
            ? ($notifiable->name[$locale] ?? $notifiable->name['en'] ?? '')
            : ($notifiable->name ?? '');

        $scheduledDate = 'N/A';
        $scheduledTime = 'N/A';
        if ($this->session->scheduled_at) {
            try {
                // $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon 
                //     ? $this->session->scheduled_at 
                //     : \Carbon\Carbon::parse($this->session->scheduled_at);
                
               // if ($scheduledAt) {
                   // $localizedDate = $scheduledAt->setLocale($locale);
                  //  if ($localizedDate) {
                        
                   // }
               // }
               $scheduledDate = $this->session->scheduled_at->format('M d, Y');
              $scheduledTime = $this->session->scheduled_at->format('g:i A');
            } catch (\Exception $e) {
                // If date parsing fails, use default values
                \Log::warning("Failed to format scheduled_at in SessionScheduledNotification::toMail", [
                    'session_id' => $this->session->id,
                    'scheduled_at' => $this->session->scheduled_at,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = (new MailMessage)
            ->theme($locale === 'ar' ? 'ar-default' : 'default')
            ->subject(__('notifications.session_scheduled_subject'))
          //  ->salutation(' ')
            ->greeting(__('notifications.hello', ['name' => $participantName]))
            ->line(__('notifications.session_scheduled_message', [
                'title' => $this->session->title ?? '',
                'date' => $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y'),
                'time' => $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A'),
                'duration' => $this->session->duration_formatted,
            ]));

        // Display mentor information in separate rows
        $message->line(__('notifications.mentor_information'))
                ->line(__('notifications.full_name_label') . ': ' . $mentorName)
                ->line(__('notifications.email_label') . ': ' . $mentorEmail);
        
        $message->line(__('notifications.session_date_label') . ': ' . $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y'));
        
        if ($competitionTitle) {
            $message->line(__('notifications.program_label') . ': ' . $competitionTitle);
        }
        
        $message->line(__('notifications.session_time_label') . ': ' . $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A'));
        
        $message
                ->line(__('notifications.session_duration_label') . ': ' . $this->session->duration_formatted);

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

        $message->line(__('notifications.session_scheduled_footer'));
        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        // Reload session from database to ensure all data is loaded (important for queued notifications)
        $this->session = $this->session->fresh(['mentor', 'participant', 'competition']);

        $mentorName = '';
        if ($this->session->mentor) {
            $mentorName = is_array($this->session->mentor->name ?? null)
                ? ($this->session->mentor->name[$locale] ?? $this->session->mentor->name['en'] ?? '')
                : ($this->session->mentor->name ?? '');
        }

        // $scheduledDate = 'N/A';
        // $scheduledTime = 'N/A';
        // if ($this->session->scheduled_at) {
        //     try {
        //         $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon 
        //             ? $this->session->scheduled_at 
        //             : \Carbon\Carbon::parse($this->session->scheduled_at);
                
        //         if ($scheduledAt) {
        //            // $localizedDate = $scheduledAt->setLocale($locale);
        //           //  if ($localizedDate) {
        //                 $scheduledDate = $scheduledAt->format('M d, Y');
        //                 $scheduledTime = $scheduledAt->format('g:i A');
        //            // }
        //         }
        //     } catch (\Exception $e) {
        //         // If date parsing fails, use default values
        //         \Log::warning("Failed to format scheduled_at in SessionScheduledNotification", [
        //             'session_id' => $this->session->id,
        //             'scheduled_at' => $this->session->scheduled_at,
        //             'error' => $e->getMessage(),
        //         ]);
        //     }
        // }

        $scheduledAtFormatted = null;
        if ($this->session->scheduled_at) {
            try {
                $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon 
                    ? $this->session->scheduled_at 
                    : \Carbon\Carbon::parse($this->session->scheduled_at);
                
                if ($scheduledAt) {
                    $scheduledAtFormatted = $scheduledAt->locale($locale)->translatedFormat('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to format scheduled_at in SessionScheduledNotification::toArray", [
                    'session_id' => $this->session->id,
                    'scheduled_at' => $this->session->scheduled_at,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Build a formatted message for the title
        $formattedMessage = __('notifications.session_scheduled_message', [
            'title' => $this->session->title ?? '',
            'date' => $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y'),
            'time' => $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A'),
            'duration' => $this->session->duration_formatted ?? 'N/A',
        ]);

        return [
            'type' => 'session_scheduled',
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

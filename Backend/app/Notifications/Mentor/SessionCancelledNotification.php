<?php

namespace App\Notifications\Mentor;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected MentorSession $session;
    protected ?string $reason;

    public function __construct(MentorSession $session, ?string $reason = null)
    {
        $this->session = $session;
        $this->reason = $reason;
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

        $userName = is_array($notifiable->name ?? null)
            ? ($notifiable->name[$locale] ?? $notifiable->name['en'] ?? '')
            : ($notifiable->name ?? '');

        $scheduledDate = 'N/A';
        $scheduledDate2 = $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y');
        $scheduledTime = 'N/A';
        $scheduledTime2 = $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A');
        if ($this->session->scheduled_at) {
            try {
                $localizedDate = $this->session->scheduled_at->setLocale($locale);
                if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                    $scheduledDate = $localizedDate->format('M d, Y');
                    $scheduledTime = $localizedDate->format('g:i A');
                }
            } catch (\Exception $e) {
                // Keep default values
            }
        }

        $message = (new MailMessage)
            ->subject(__('notifications.session_cancelled_subject'))
            ->greeting(__('notifications.hello', ['name' => $userName]))
            ->line(__('notifications.session_cancelled_message', [
                //'title' => $this->session->title,
                'date' => $scheduledDate2,
                'time' => $scheduledTime2,
            ]));

        if ($this->reason) {
            $message->line(__('notifications.cancellation_reason'))
                ->line($this->reason);
        }

        $message->line(__('notifications.session_cancelled_footer'));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        // Reload session from database to ensure all data is loaded (important for queued notifications)
        $this->session = $this->session->fresh(['mentor', 'participant', 'competition']);

        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $participantName = null;
        if ($this->session->participant) {
            $participantName = is_array($this->session->participant->name ?? null)
                ? ($this->session->participant->name[$locale] ?? $this->session->participant->name['en'] ?? '')
                : ($this->session->participant->name ?? '');
        }

        $scheduledDate = 'N/A';
        $scheduledDate2 = $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y');
        $scheduledTime = 'N/A';
        $scheduledTime2 = $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A');
        if ($this->session->scheduled_at) {
            try {
                $localizedDate = $this->session->scheduled_at->setLocale($locale);
                if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                    $scheduledDate = $localizedDate->format('M d, Y');
                    $scheduledTime = $localizedDate->format('g:i A');
                }
            } catch (\Exception $e) {
                // Keep default values
            }
        }

        return [
            'type' => 'session_cancelled',
            'session_id' => $this->session->id,
            'title' => $this->session->title,
            'scheduled_at' => $this->session->scheduled_at ? $this->session->scheduled_at->format('Y-m-d H:i:s') : null,
            'duration_minutes' => $this->session->duration_minutes,
            'participant_name' => $participantName,
            'reason' => $this->reason,
            'message' => __('notifications.session_cancelled_message', [
                //'title' => $this->session->title,
                'date' => $scheduledDate2,
                'time' => $scheduledTime2,
            ]),
        ];
    }
}

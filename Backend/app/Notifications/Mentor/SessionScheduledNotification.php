<?php

namespace App\Notifications\Mentor;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionScheduledNotification extends Notification implements ShouldQueue
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
        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $mentorName = is_array($notifiable->name ?? null)
            ? ($notifiable->name[$locale] ?? $notifiable->name['en'] ?? '')
            : ($notifiable->name ?? '');

        // Laravel automatically converts scheduled_at from UTC to app timezone (Asia/Riyadh) when reading from DB
        // So scheduled_at is already in Asia/Riyadh timezone
        $scheduledDate = $this->session->scheduled_at
            ? $this->session->scheduled_at->copy()->setLocale($locale)->format('M d, Y')
            : 'N/A';
        $scheduledTime = $this->session->scheduled_at
            ? $this->session->scheduled_at->copy()->setLocale($locale)->format('g:i A')
            : 'N/A';

        $message = (new MailMessage)
            ->subject(__('notifications.session_scheduled_subject'))
            ->greeting(__('notifications.hello', ['name' => $mentorName]))
            ->line(__('notifications.session_scheduled_message', [
                'title' => $this->session->title,
                'date' => $scheduledDate,
                'time' => $scheduledTime,
                'duration' => $this->session->duration_formatted,
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

        $participantName = null;
        if ($this->session->participant) {
            $participantName = is_array($this->session->participant->name ?? null)
                ? ($this->session->participant->name[$locale] ?? $this->session->participant->name['en'] ?? '')
                : ($this->session->participant->name ?? '');
        }

        // Laravel automatically converts scheduled_at from UTC to app timezone (Asia/Riyadh) when reading from DB
        // So scheduled_at is already in Asia/Riyadh timezone
        $scheduledDate = $this->session->scheduled_at
            ? $this->session->scheduled_at->copy()->setLocale($locale)->format('M d, Y')
            : 'N/A';
        $scheduledTime = $this->session->scheduled_at
            ? $this->session->scheduled_at->copy()->setLocale($locale)->format('g:i A')
            : 'N/A';

        return [
            'type' => 'session_scheduled',
            'session_id' => $this->session->id,
            'title' => $this->session->title,
            'scheduled_at' => $this->session->scheduled_at ? $this->session->scheduled_at->format('Y-m-d H:i:s') : null,
            'duration_minutes' => $this->session->duration_minutes,
            'join_url' => $this->session->join_url,
            'video_tool' => $this->session->video_tool,
            'participant_name' => $participantName,
            'message' => __('notifications.session_scheduled_message', [
                'title' => $this->session->title,
                'date' => $scheduledDate,
                'time' => $scheduledTime,
                'duration' => $this->session->duration_formatted,
            ]),
        ];
    }
}

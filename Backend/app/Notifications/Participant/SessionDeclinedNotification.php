<?php

namespace App\Notifications\Participant;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionDeclinedNotification extends Notification implements ShouldQueue
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
        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $mentorName = is_array($this->session->mentor->name ?? null) 
            ? ($this->session->mentor->name[$locale] ?? $this->session->mentor->name['en'] ?? '') 
            : ($this->session->mentor->name ?? '');

        $participantName = is_array($notifiable->name ?? null)
            ? ($notifiable->name[$locale] ?? $notifiable->name['en'] ?? '')
            : ($notifiable->name ?? '');

        $scheduledDate = $this->session->scheduled_at 
            ? $this->session->scheduled_at->format('M d, Y')
            : 'N/A';
        $scheduledTime = $this->session->scheduled_at 
            ? $this->session->scheduled_at->format('g:i A')
            : 'N/A';

        $message = (new MailMessage)
            ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
            ->subject(__('notifications.session_declined_subject'))
            ->greeting(__('notifications.hello', ['name' => $participantName]))
            ->line(__('notifications.session_declined_message', [
                'mentor_name' => $mentorName,
                'title' => $this->session->title,
                'date' => $scheduledDate,
                'time' => $scheduledTime,
            ]));

        if ($this->reason) {
            $message->line(__('notifications.decline_reason_label'))
                ->line($this->reason);
        }

        $message->line(__('notifications.session_declined_footer'));

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

        $mentorName = is_array($this->session->mentor->name ?? null) 
            ? ($this->session->mentor->name[$locale] ?? $this->session->mentor->name['en'] ?? '') 
            : ($this->session->mentor->name ?? '');

        $scheduledDate = $this->session->scheduled_at 
            ? $this->session->scheduled_at->setLocale($locale)->format('M d, Y')
            : 'N/A';
        $scheduledTime = $this->session->scheduled_at 
            ? $this->session->scheduled_at->setLocale($locale)->format('g:i A')
            : 'N/A';

        return [
            'type' => 'session_declined',
            'session_id' => $this->session->id,
            'title' => $this->session->title,
            'scheduled_at' => $this->session->scheduled_at ? $this->session->scheduled_at->format('Y-m-d H:i:s') : null,
            'mentor_name' => $mentorName,
            'reason' => $this->reason,
            'message' => __('notifications.session_declined_message', [
                'mentor_name' => $mentorName,
                'title' => $this->session->title,
                'date' => $scheduledDate,
                'time' => $scheduledTime,
            ]),
        ];
    }
}


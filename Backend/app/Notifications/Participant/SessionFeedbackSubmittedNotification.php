<?php

namespace App\Notifications\Participant;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionFeedbackSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected MentorSession $session)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.session_feedback_submitted_subject'))
            ->greeting(__('notifications.hello', ['name' => $notifiable->name['en'] ?? '']))
            ->line(__('notifications.session_feedback_submitted_message', [
                'title' => $this->session->title,
            ]))
            ->line(__('notifications.view_in_portal'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'session_feedback_submitted',
            'session_id' => $this->session->id,
            'title' => $this->session->title,
            'mentor_name' => $this->session->mentor->name,
        ];
    }
}




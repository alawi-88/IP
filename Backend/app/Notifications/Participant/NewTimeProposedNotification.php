<?php

namespace App\Notifications\Participant;

use App\Models\MentorSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTimeProposedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected MentorSession $session;
    protected Carbon $proposedTime;

    public function __construct(MentorSession $session, Carbon $proposedTime)
    {
        $this->session = $session;
        $this->proposedTime = $proposedTime;
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
        $mentorName = is_array($this->session->mentor->name ?? null) 
            ? ($this->session->mentor->name['en'] ?? '') 
            : ($this->session->mentor->name ?? '');

        $originalDate = $this->session->scheduled_at 
            ? $this->session->scheduled_at->format('M d, Y')
            : 'N/A';
        $originalTime = $this->session->scheduled_at 
            ? $this->session->scheduled_at->format('g:i A')
            : 'N/A';

        $message = (new MailMessage)
            ->subject(__('notifications.new_time_proposed_subject'))
            //->salutation(' ')
            ->greeting(__('notifications.hello', ['name' => $notifiable->name['en'] ?? '']))
            ->line(__('notifications.new_time_proposed_message', [
                'mentor_name' => $mentorName,
                'title' => $this->session->title,
                'original_date' => $originalDate,
                'original_time' => $originalTime,
                'proposed_date' => $this->proposedTime->format('M d, Y'),
                'proposed_time' => $this->proposedTime->format('g:i A'),
                'duration' => $this->session->duration_formatted,
            ]));

        $message->line(__('notifications.new_time_proposed_instructions'));

        if ($this->session->description) {
            $message->line(__('notifications.session_description'))
                ->line($this->session->description);
        }

        $message->line(__('notifications.new_time_proposed_footer'));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $mentorName = is_array($this->session->mentor->name ?? null) 
            ? ($this->session->mentor->name['en'] ?? '') 
            : ($this->session->mentor->name ?? '');

        $originalDate = $this->session->scheduled_at 
            ? $this->session->scheduled_at->format('M d, Y')
            : 'N/A';
        $originalTime = $this->session->scheduled_at 
            ? $this->session->scheduled_at->format('g:i A')
            : 'N/A';

        return [
            'type' => 'new_time_proposed',
            'session_id' => $this->session->id,
            'title' => $this->session->title,
            'original_scheduled_at' => $this->session->scheduled_at ? $this->session->scheduled_at->format('Y-m-d H:i:s') : null,
            'proposed_time' => $this->proposedTime->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->session->duration_minutes,
            'mentor_name' => $mentorName,
            'message' => __('notifications.new_time_proposed_message', [
                'mentor_name' => $mentorName,
                'title' => $this->session->title,
                'original_date' => $originalDate,
                'original_time' => $originalTime,
                'proposed_date' => $this->proposedTime->format('M d, Y'),
                'proposed_time' => $this->proposedTime->format('g:i A'),
                'duration' => $this->session->duration_formatted,
            ]),
        ];
    }
}


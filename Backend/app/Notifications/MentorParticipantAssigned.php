<?php

namespace App\Notifications;

use App\Models\Mentor;
use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorParticipantAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Mentor $mentor,
        public Participant $participant,
        public string $recipientType = 'participant' // 'mentor' or 'participant'
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mentorName = is_array($this->mentor->name)
            ? ($this->mentor->name['en'] ?? $this->mentor->name['ar'] ?? 'Mentor')
            : $this->mentor->name;

        $participantName = is_array($this->participant->name)
            ? ($this->participant->name['en'] ?? $this->participant->name['ar'] ?? 'Participant')
            : $this->participant->name;

        if ($this->recipientType === 'mentor') {
            // Email to mentor
            return (new MailMessage)
                ->subject(__('mentor.participant_assigned_subject'))
                ->greeting(__('mentor.hello', ['name' => $mentorName]))
                ->line(__('mentor.participant_assigned_to_you', ['name' => $participantName, 'email' => $this->participant->email]))
                ->line("---")
                ->line(__('mentor.you_can_now_guide_participant'));
        }

        // Email to participant (default)
        return (new MailMessage)
            ->subject(__('mentor.mentor_assigned_subject'))
            ->greeting(__('mentor.hello', ['name' => $participantName]))
            ->line(__('mentor.a_mentor_has_been_assigned_to_guide_you', ['name' => $mentorName]))
            ->line("---")
            ->line(__('mentor.good_luck_with_your_project'));
    }

    public function toDatabase($notifiable): array
    {
        $mentorName = is_array($this->mentor->name)
            ? ($this->mentor->name['en'] ?? $this->mentor->name['ar'] ?? 'Mentor')
            : $this->mentor->name;

        $participantName = is_array($this->participant->name)
            ? ($this->participant->name['en'] ?? $this->participant->name['ar'] ?? 'Participant')
            : $this->participant->name;

        if ($this->recipientType === 'mentor') {
            // Database notification for mentor
            return [
                'title' => __('mentor.participant_assigned_title'),
                'body' => __('mentor.participant_assigned_body', ['name' => $participantName, 'email' => $this->participant->email]),
                'icon' => 'heroicon-o-user-plus',
                'iconColor' => 'success',
                'color' => 'success',
                'status' => 'success',
                'mentor_id' => $this->mentor->id,
                'participant_id' => $this->participant->id,
                'participant_name' => $participantName,
                'participant_email' => $this->participant->email,
                'actions' => [
                    [
                        'name' => 'view',
                        'label' => __('mentor.view_participants'),
                        'url' => url('/mentor/teams'),
                    ],
                ],
                'format' => 'filament',
            ];
        }

        // Database notification for participant (default)
        return [
            'title' => __('mentor.mentor_assigned_title'),
            'body' => __('mentor.mentor_assigned_body', ['name' => $mentorName]),
            'icon' => 'heroicon-o-user',
            'iconColor' => 'success',
            'color' => 'success',
            'status' => 'success',
            'mentor_id' => $this->mentor->id,
            'mentor_name' => $mentorName,
            'participant_id' => $this->participant->id,
            'actions' => [
                [
                    'name' => 'view',
                    'label' => __('mentor.view_dashboard'),
                    'url' => url('/participant/dashboard'),
                ],
            ],
            'format' => 'filament',
        ];
    }
}

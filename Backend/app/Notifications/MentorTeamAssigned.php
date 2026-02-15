<?php

namespace App\Notifications;

use App\Models\Mentor;
use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorTeamAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Mentor $mentor,
        public Team $team,
        public string $recipientType = 'mentor' // 'mentor' or 'team'
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

        $teamName = $this->team->name ?? 'Team';

        if ($this->recipientType === 'mentor') {
            return (new MailMessage)
                ->subject("New Team Assignment - تعيين فريق جديد")
                ->greeting(__('mentor.hello', ['name' => $mentorName]))
                ->line(__('mentor.you_have_been_assigned_to_team', ['name' => $teamName]))
                ->line("---")
                //->action(__('mentor.view_team_details'), url('/mentor/teams'))
                ->line(__('mentor.thank_you_for_being_a_mentor'));
        }

        // For team members
        return (new MailMessage)
            ->subject("Mentor Assigned to Your Team - تم تعيين مرشد لفريقك")
            //->salutation(' ')
            ->greeting(__('mentor.hello', ['name' => $notifiable->name]))
            ->line(__('mentor.a_mentor_has_been_assigned_to_your_team', ['name' => $teamName]))
            ->line(__('mentor.mentor', ['name' => $mentorName]))
            ->line("---")
           // ->line(__('mentor.mentor_assigned_to_your_team', ['name' => $teamName]))
           // ->line(__('mentor.mentor', ['name' => $mentorName]))
           // ->action(__('mentor.view_mentor'), url('/participant/mentors'))
            ->line(__('mentor.good_luck_with_your_project', ['name' => $mentorName]))
           ;
    }

    public function toDatabase($notifiable): array
    {
        $mentorName = is_array($this->mentor->name) 
            ? ($this->mentor->name['en'] ?? $this->mentor->name['ar'] ?? 'Mentor') 
            : $this->mentor->name;

        $teamName = $this->team->name ?? 'Team';

        // Title based on recipient type
        $title = $this->recipientType === 'mentor'
            ? "Team Assignment / تعيين فريق"
            : "Mentor Assigned / تم تعيين مرشد";

        // Body based on recipient type
        $body = $this->recipientType === 'mentor'
            ? "You have been assigned to team: {$teamName} / تم تعيينك للفريق: {$teamName}"
            : "Mentor {$mentorName} has been assigned to your team / تم تعيين المرشد {$mentorName} لفريقك";

        // Filament notification format
        return [
            // Standard Filament notification keys
            'title' => $title,
            'body' => $body,
            'icon' => 'heroicon-o-user-group',
            'iconColor' => 'success',
            'color' => 'success',
            'status' => 'success',
            
            // Custom data for reference
            'mentor_id' => $this->mentor->id,
            'mentor_name' => $mentorName,
            'team_id' => $this->team->id,
            'team_name' => $teamName,
            'recipient_type' => $this->recipientType,
            
            // Actions (optional - for clickable notifications)
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'View / عرض',
                    'url' => $this->recipientType === 'mentor' 
                        ? url('/mentor/teams') 
                        : url('/participant/mentors'),
                ],
            ],
            
            // Format for Filament's DatabaseNotification
            'format' => 'filament',
        ];
    }
}

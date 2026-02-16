<?php

namespace App\Notifications\Participant;

use App\Models\TaskAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected TaskAssignment $assignment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $taskTitle = $this->assignment->getTranslation('title', 'en');
        $competitionTitle = $this->assignment->competition?->getTranslation('title', 'en') ?? 'Unknown Program';
        $dueDate = $this->assignment->due_date?->format('M d, Y') ?? 'No deadline';

        return (new MailMessage)
            ->subject("New Task Assigned - {$competitionTitle}")
            ->greeting('Dear Participant,')
            ->line("A new task has been assigned to you in **{$competitionTitle}**.")
            ->line("**Task:** {$taskTitle}")
            ->line("**Due Date:** {$dueDate}")
            ->line('Please log in to your account to view the task details and submit your deliverables.')
            ->line('Thank you for your participation.');
    }

    public function toArray(object $notifiable): array
    {
        $competitionTitle = $this->assignment->competition?->title;

        return [
            'title' => [
                'en' => 'New Task Assigned',
                'ar' => 'مهمة جديدة معينة',
            ],
            'body' => [
                'en' => "You have been assigned a new task: " . $this->assignment->getTranslation('title', 'en') . " in " . (is_array($competitionTitle) ? ($competitionTitle['en'] ?? '') : $competitionTitle) . ". Due: " . ($this->assignment->due_date?->format('M d, Y') ?? 'N/A'),
                'ar' => "تم تعيين مهمة جديدة لك: " . $this->assignment->getTranslation('title', 'ar') . " في " . (is_array($competitionTitle) ? ($competitionTitle['ar'] ?? '') : $competitionTitle),
            ],
            'task_assignment_id' => $this->assignment->id,
            'competition_id' => $this->assignment->competition_id,
        ];
    }
}

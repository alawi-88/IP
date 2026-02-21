<?php

namespace App\Notifications\Participant;

use App\Models\TaskAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected TaskAssignment $assignment,
        protected string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $taskTitle = $this->assignment->getTranslation('title', 'en');
        $programTitle = $this->assignment->program?->getTranslation('title', 'en') ?? 'Unknown Program';
        $statusLabel = ucwords(str_replace('_', ' ', $this->newStatus));

        $mail = (new MailMessage)
            ->subject("Task Update: {$statusLabel} - {$programTitle}")
            ->greeting('Dear Participant,')
            ->line("Your task **{$taskTitle}** in **{$programTitle}** has been updated.")
            ->line("**New Status:** {$statusLabel}");

        if ($this->newStatus === 'revision_requested') {
            $feedback = $this->assignment->latestSubmission?->admin_feedback;
            if ($feedback) {
                $mail->line("**Revision Notes:** {$feedback}");
            }
            $mail->line('Please log in to review the feedback and resubmit your deliverables.');
        } elseif ($this->newStatus === 'approved') {
            $mail->line('Congratulations! Your task submission has been approved.');
        } elseif ($this->newStatus === 'rejected') {
            $feedback = $this->assignment->latestSubmission?->admin_feedback;
            if ($feedback) {
                $mail->line("**Reason:** {$feedback}");
            }
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $statusLabels = [
            'approved' => ['en' => 'Approved', 'ar' => 'مقبول'],
            'rejected' => ['en' => 'Rejected', 'ar' => 'مرفوض'],
            'revision_requested' => ['en' => 'Revision Requested', 'ar' => 'مطلوب تعديل'],
        ];

        $label = $statusLabels[$this->newStatus] ?? ['en' => ucwords(str_replace('_', ' ', $this->newStatus)), 'ar' => $this->newStatus];

        return [
            'title' => [
                'en' => "Task {$label['en']}",
                'ar' => "المهمة {$label['ar']}",
            ],
            'body' => [
                'en' => "Your task \"{$this->assignment->getTranslation('title', 'en')}\" has been marked as {$label['en']}.",
                'ar' => "تم تحديث مهمتك \"{$this->assignment->getTranslation('title', 'ar')}\" إلى {$label['ar']}.",
            ],
            'task_assignment_id' => $this->assignment->id,
            'program_id' => $this->assignment->program_id,
            'new_status' => $this->newStatus,
        ];
    }
}

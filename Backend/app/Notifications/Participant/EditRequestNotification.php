<?php

namespace App\Notifications\Participant;

use App\Models\CompetitionApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EditRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected CompetitionApplication $application
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $competitionTitle = $this->application->competition?->getTranslation('title', 'en') ?? 'Unknown Program';
        $editNotes = $this->application->edit_notes;
        $notesEn = is_array($editNotes) ? ($editNotes['en'] ?? '') : ($editNotes ?? '');

        return (new MailMessage)
            ->subject("Edit Request - {$competitionTitle}")
            ->greeting('Dear Participant,')
            ->line("The administrators have requested changes to your application for **{$competitionTitle}**.")
            ->line("**Instructions:** {$notesEn}")
            ->line('Please log in to your account to make the requested changes.')
            ->line('Thank you for your participation.');
    }

    public function toArray(object $notifiable): array
    {
        $competitionTitle = $this->application->competition?->title;
        $editNotes = $this->application->edit_notes;

        return [
            'title' => [
                'en' => 'Edit Request for Your Application',
                'ar' => 'طلب تعديل لطلبك',
            ],
            'body' => [
                'en' => "Changes have been requested for your application in " . (is_array($competitionTitle) ? ($competitionTitle['en'] ?? '') : $competitionTitle) . ". " . (is_array($editNotes) ? ($editNotes['en'] ?? '') : ''),
                'ar' => "تم طلب تغييرات على طلبك في " . (is_array($competitionTitle) ? ($competitionTitle['ar'] ?? '') : $competitionTitle) . ". " . (is_array($editNotes) ? ($editNotes['ar'] ?? '') : ''),
            ],
            'application_id' => $this->application->id,
            'competition_id' => $this->application->competition_id,
            'editable_fields' => $this->application->editable_fields,
        ];
    }
}

<?php

namespace App\Notifications\Participant;

use App\Models\ProgramApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EditRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ProgramApplication $application
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $programTitle = $this->application->program?->getTranslation('title', 'en') ?? 'Unknown Program';
        $editNotes = $this->application->edit_notes;
        $notesEn = is_array($editNotes) ? ($editNotes['en'] ?? '') : ($editNotes ?? '');

        return (new MailMessage)
            ->subject("Edit Request - {$programTitle}")
            ->greeting('Dear Participant,')
            ->line("The administrators have requested changes to your application for **{$programTitle}**.")
            ->line("**Instructions:** {$notesEn}")
            ->line('Please log in to your account to make the requested changes.')
            ->line('Thank you for your participation.');
    }

    public function toArray(object $notifiable): array
    {
        $programTitle = $this->application->program?->title;
        $editNotes = $this->application->edit_notes;

        return [
            'title' => [
                'en' => 'Edit Request for Your Application',
                'ar' => 'طلب تعديل لطلبك',
            ],
            'body' => [
                'en' => "Changes have been requested for your application in " . (is_array($programTitle) ? ($programTitle['en'] ?? '') : $programTitle) . ". " . (is_array($editNotes) ? ($editNotes['en'] ?? '') : ''),
                'ar' => "تم طلب تغييرات على طلبك في " . (is_array($programTitle) ? ($programTitle['ar'] ?? '') : $programTitle) . ". " . (is_array($editNotes) ? ($editNotes['ar'] ?? '') : ''),
            ],
            'application_id' => $this->application->id,
            'program_id' => $this->application->program_id,
            'editable_fields' => $this->application->editable_fields,
        ];
    }
}

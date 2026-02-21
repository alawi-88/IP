<?php

namespace App\Notifications\Participant;

use App\Models\ProgramApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ProgramApplication $application,
        protected string $decision,
        protected ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $programTitle = $this->application->program?->getTranslation('title', 'en') ?? 'Unknown Program';
        $isApproved = $this->decision === 'approved';

        $mail = (new MailMessage)
            ->subject($isApproved
                ? "Application Approved - {$programTitle}"
                : "Application Update - {$programTitle}")
            ->greeting('Dear Participant,')
            ->line($isApproved
                ? "Your application for **{$programTitle}** has been approved!"
                : "Your application for **{$programTitle}** has been reviewed.");

        if (!$isApproved && $this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        $mail->line($isApproved
            ? 'You can now proceed to the next steps in the program.'
            : 'Please check your application for more details.');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $programTitle = $this->application->program?->title;

        return [
            'title' => [
                'en' => $this->decision === 'approved'
                    ? 'Application Approved'
                    : 'Application Decision',
                'ar' => $this->decision === 'approved'
                    ? 'تم قبول الطلب'
                    : 'قرار بشأن الطلب',
            ],
            'body' => [
                'en' => $this->decision === 'approved'
                    ? "Your application for " . (is_array($programTitle) ? ($programTitle['en'] ?? '') : $programTitle) . " has been approved."
                    : "Your application for " . (is_array($programTitle) ? ($programTitle['en'] ?? '') : $programTitle) . " has been reviewed. " . ($this->reason ? "Reason: {$this->reason}" : ''),
                'ar' => $this->decision === 'approved'
                    ? "تم قبول طلبك في " . (is_array($programTitle) ? ($programTitle['ar'] ?? '') : $programTitle)
                    : "تمت مراجعة طلبك في " . (is_array($programTitle) ? ($programTitle['ar'] ?? '') : $programTitle) . ($this->reason ? ". السبب: {$this->reason}" : ''),
            ],
            'application_id' => $this->application->id,
            'program_id' => $this->application->program_id,
            'decision' => $this->decision,
        ];
    }
}

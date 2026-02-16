<?php

namespace App\Notifications\Participant;

use App\Models\CompetitionApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected CompetitionApplication $application,
        protected string $decision,
        protected ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $competitionTitle = $this->application->competition?->getTranslation('title', 'en') ?? 'Unknown Program';
        $isApproved = $this->decision === 'approved';

        $mail = (new MailMessage)
            ->subject($isApproved
                ? "Application Approved - {$competitionTitle}"
                : "Application Update - {$competitionTitle}")
            ->greeting('Dear Participant,')
            ->line($isApproved
                ? "Your application for **{$competitionTitle}** has been approved!"
                : "Your application for **{$competitionTitle}** has been reviewed.");

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
        $competitionTitle = $this->application->competition?->title;

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
                    ? "Your application for " . (is_array($competitionTitle) ? ($competitionTitle['en'] ?? '') : $competitionTitle) . " has been approved."
                    : "Your application for " . (is_array($competitionTitle) ? ($competitionTitle['en'] ?? '') : $competitionTitle) . " has been reviewed. " . ($this->reason ? "Reason: {$this->reason}" : ''),
                'ar' => $this->decision === 'approved'
                    ? "تم قبول طلبك في " . (is_array($competitionTitle) ? ($competitionTitle['ar'] ?? '') : $competitionTitle)
                    : "تمت مراجعة طلبك في " . (is_array($competitionTitle) ? ($competitionTitle['ar'] ?? '') : $competitionTitle) . ($this->reason ? ". السبب: {$this->reason}" : ''),
            ],
            'application_id' => $this->application->id,
            'competition_id' => $this->application->competition_id,
            'decision' => $this->decision,
        ];
    }
}

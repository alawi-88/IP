<?php

namespace App\Notifications;

use App\Models\CompetitionApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
class CompetitionRegistration extends Notification
{
    use Queueable, HasEmailTemplate;

    /**
     * Create a new notification instance.
     */
    public function __construct(public CompetitionApplication $application)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
    //         ->subject(__('competition_application.Participation Request Confirmation!', ['competition' => $this->application?->competition?->title]))
    //         ->greeting(__('competition_application.Dear Participant,'))
    //         ->line(__('competition_application.application_received_message', ['competition' => $this->application?->competition?->title]))
    //         ->line(__('competition_application.We wish you success.'));
    // }

    public function toMail(object $notifiable): MailMessage
{
    if($this->application->type === 'draft'){
        return null;
    }
    $competitionName = $this->application?->competition?->title ?? '';

    // Try to get template from DB
    $data = $this->renderEmailTemplate('user.competition_confirmation', [
        'competition' => $competitionName,
        'name' => $notifiable->name,
    ]);
    if ($data && !empty($data['body']) && $data['body'] !== null) {
        $body = $data['body'];
        $subject = $data['subject'];

        return (new MailMessage)
            ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
            ->subject($subject)
            ->greeting(' ') 
            ->salutation(' ')
            ->line(new \Illuminate\Support\HtmlString($body));
            
    } else {
            return (new MailMessage)
            ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
            //->salutation(' ')
            ->subject(__('competition_application.Participation Request Confirmation!', ['competition' => $this->application?->competition?->title]))
            ->greeting(__('competition_application.Dear Participant,'))
            ->line(__('competition_application.application_received_message', ['competition' => $this->application?->competition?->title]))
            ->line(__('competition_application.We wish you success.'));
    }

    
}


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => getMultilingualTranslation('competition_application.Participation Request Confirmation!'),
        ];
    }
}

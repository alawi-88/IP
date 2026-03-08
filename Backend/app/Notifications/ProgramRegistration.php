<?php

namespace App\Notifications;

use App\Models\ProgramApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
class ProgramRegistration extends Notification
{
    use Queueable, HasEmailTemplate;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ProgramApplication $application)
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
    //         ->subject(__('program_application.Participation Request Confirmation!', ['program' => $this->application?->program?->title]))
    //         ->greeting(__('program_application.Dear Participant,'))
    //         ->line(__('program_application.application_received_message', ['program' => $this->application?->program?->title]))
    //         ->line(__('program_application.We wish you success.'));
    // }

    public function toMail(object $notifiable): MailMessage
{
    if($this->application->type === 'draft'){
        return null;
    }
    $programName = $this->application?->program?->title ?? '';

    // Try to get template from DB
    $data = $this->renderEmailTemplate('user.program_confirmation', [
        'program' => $programName,
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
            ->subject(__('program_application.Participation Request Confirmation!', ['program' => $this->application?->program?->title]))
            ->greeting(__('program_application.Dear Participant,'))
            ->line(__('program_application.application_received_message', ['program' => $this->application?->program?->title]))
            ->line(__('program_application.We wish you success.'));
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
            'title' => getMultilingualTranslation('program_application.Participation Request Confirmation!'),
        ];
    }
}

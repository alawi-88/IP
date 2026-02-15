<?php

namespace App\Notifications\Mentor;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;

class MentorDeactivated extends Notification
{
    use Queueable, HasEmailTemplate;

    public function __construct(private $mentor)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->renderEmailTemplate('mentor.deactivated', [
            'name' => $this->mentor->name,
            'email' => $this->mentor->email,
            'deactivated_at' => now()->format('Y-m-d H:i:s')
        ]);
        
        if ($data && !empty($data['body']) && $data['body'] !== null) {
            $body = $data['body'];
            $subject = $data['subject'];
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->greeting(' ')
                ->salutation(' ')
                ->subject($subject)
                ->line(new \Illuminate\Support\HtmlString($body));
        } else {
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->greeting(' ')
               // ->salutation(' ')
                ->subject(__('mentor.deactivated_subject'))
                ->line(__('mentor.deactivated_message', ['name' => $this->mentor->name]))
                ->line(__('mentor.deactivated_details'));
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'mentor_deactivated',
            'mentor_id' => $this->mentor->id,
            'mentor_name' => $this->mentor->name,
            'mentor_email' => $this->mentor->email,
            'deactivated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}


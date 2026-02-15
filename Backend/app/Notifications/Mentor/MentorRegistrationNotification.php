<?php

namespace App\Notifications\Mentor;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;

class MentorRegistrationNotification extends Notification
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
        $data = $this->renderEmailTemplate('mentor.admin_registration_notification', [
            'name' => $this->mentor->name,
            'email' => $this->mentor->email,
            'phone' => $this->mentor->phone,
            'profession' => $this->mentor->profession,
            'experience' => $this->mentor->experience,
            'date' => $this->mentor->created_at->format('Y-m-d H:i:s')
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
                ->salutation(' ')
                ->subject(__('mentor.admin_registration_subject'))
                ->line(__('mentor.admin_registration_message'))
                ->line(__('mentor.admin_registration_details', [
                    'name' => $this->mentor->name,
                    'email' => $this->mentor->email,
                    'phone' => $this->mentor->phone,
                    'profession' => $this->mentor->profession,
                    'experience' => $this->mentor->experience,
                    'date' => $this->mentor->created_at->format('Y-m-d H:i:s')
                ]));
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'mentor_registration_notification',
            'mentor_id' => $this->mentor->id,
            'mentor_name' => $this->mentor->name,
            'mentor_email' => $this->mentor->email,
            'mentor_phone' => $this->mentor->phone,
            'registration_date' => $this->mentor->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

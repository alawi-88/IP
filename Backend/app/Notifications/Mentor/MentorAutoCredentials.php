<?php

namespace App\Notifications\Mentor;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;

class MentorAutoCredentials extends Notification
{
    use Queueable, HasEmailTemplate;

    public function __construct(private $mentor, private $plainPassword)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->renderEmailTemplate('mentor.auto_credentials', [
            'name' => $this->mentor->name,
            'email' => $this->mentor->email,
            'password' => $this->plainPassword,
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
            // Fallback message
            $subject = __('mentor.auto_credentials_subject');
            $message = __('mentor.auto_credentials_message', [
                'name' => $this->mentor->name,
                'email' => $this->mentor->email,
                'password' => $this->plainPassword,
            ]);
            
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->subject($subject)
                ->greeting(__('mentor.auto_credentials_greeting', ['name' => $this->mentor->name]))
                ->line(__('mentor.auto_credentials_intro'))
                ->line(__('mentor.auto_credentials_email_label') . ': ' . $this->mentor->email)
                ->line(__('mentor.auto_credentials_password_label') . ': ' . $this->plainPassword)
                ->line(__('mentor.auto_credentials_footer'))
                ->action(__('mentor.login_button'), rtrim(config('app.frontend_url'), '/') . '/en/mentor/login');
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'mentor_auto_credentials_sent',
            'mentor_id' => $this->mentor->id,
            'mentor_name' => $this->mentor->name,
            'mentor_email' => $this->mentor->email,
        ];
    }
}


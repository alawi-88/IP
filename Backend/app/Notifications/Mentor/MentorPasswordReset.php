<?php

namespace App\Notifications\Mentor;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EmailTemplate;
use App\Traits\HasEmailTemplate;

class MentorPasswordReset extends Notification
{
    use Queueable, HasEmailTemplate;

    /**
     * Create a new notification instance.
     */
    public function __construct(private $code)
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
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->renderEmailTemplate('mentor.forgot_password', ['code' => $this->code, 'name' => $notifiable->name]);
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
                ->subject(__('mentor.reset_password'))
                ->line(__('mentor.reset_password_message'))
                ->line(__('mentor.reset_password_code', ['code' => $this->code]));
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
            'message' => 'reset_password',
        ];
    }
}

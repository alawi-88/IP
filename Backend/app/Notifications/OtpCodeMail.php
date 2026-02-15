<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EmailTemplate;
use App\Traits\HasEmailTemplate;
class OtpCodeMail extends Notification
{
    use Queueable, HasEmailTemplate;

    /**
     * Create a new notification instance.
     */
    public function __construct(private $otpCode)
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $lang = app()->getLocale(); // أو من user->lang
        $template = $this->renderEmailTemplate('user.otp_login', ['otpCode' => $this->otpCode]);
        if ($template && !empty($template['body']) && $template['body'] !== null) {
            $body = $template['body'];
            $subject = $template['subject'];
            return (new MailMessage)   
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->greeting(' ')
                ->salutation(' ')
                ->subject($subject)
                ->line(new \Illuminate\Support\HtmlString($body));
        } else {
            $subject = 'OTP Code';
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->subject($subject)
                ->greeting(' ')
                //->salutation(' ')
                //->salutation(__('mail.salutation'))
                ->line('OTP Code')
                ->line('Your OTP code is: ' . $this->otpCode);
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

        ];
    }
}

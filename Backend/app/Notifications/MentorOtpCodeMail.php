<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EmailTemplate;
use App\Traits\HasEmailTemplate;

class MentorOtpCodeMail extends Notification
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
        $lang = app()->getLocale();
        $template = $this->renderEmailTemplate('mentor.otp_login', ['otpCode' => $this->otpCode]);
        
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
            // Fallback to language files
            $subject = __('mentor.otp_email_subject');
            $greeting = __('mentor.otp_email_greeting');
            $message = __('mentor.otp_email_message', ['email' => $notifiable->email]);
            $code = __('mentor.otp_email_code', ['code' => $this->otpCode]);
            //$expires = __('mentor.otp_email_expires');
            $signature = __('mentor.otp_email_signature');
            
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->subject($subject)
                ->greeting($greeting)
                ->line($message)
                ->line($code);
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
            'otp_code' => $this->otpCode,
        ];
    }
}

<?php

namespace App\Notifications\Participant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EmailTemplate;
use App\Traits\HasEmailTemplate;

class LoginOtpMail extends Notification
{
    use Queueable, HasEmailTemplate;

    public string $otpCode;
    public string $loginEmail;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otpCode, string $loginEmail)
    {
        $this->otpCode = $otpCode;
        $this->loginEmail = $loginEmail;
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
        $template = $this->renderEmailTemplate('user.otp_login', [
            'otpCode' => $this->otpCode,
            'loginEmail' => $this->loginEmail
        ]);
        
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
            $subject = __('auth.login_otp_subject', ['app' => config('app.name')]);
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->subject($subject)
                ->greeting(__('auth.login_otp_greeting'))
                ->line(__('auth.login_otp_message', ['email' => $this->loginEmail]))
                ->line(__('auth.login_otp_code', ['code' => $this->otpCode]))
                ->line(__('auth.login_otp_expires'))
                ->salutation(__('auth.login_otp_signature', ['app' => config('app.name')]));
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
            //
        ];
    }
}

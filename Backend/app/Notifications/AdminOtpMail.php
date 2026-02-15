<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\MonshaatEmailService;
use App\Traits\HasEmailTemplate;
class AdminOtpMail extends Notification
{
    use Queueable;
    use HasEmailTemplate;
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
        $data = $this->renderEmailTemplate('admin.otp_login', ['otpCode' => $this->otpCode]);
        if ($data) {
            $body = $data['body'];
            $subject = $data['subject'];
            $greeting = ' ';
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->greeting($greeting)
                ->salutation(' ')
                ->subject($subject)
                ->line(new \Illuminate\Support\HtmlString($body));
        } else {
            return (new MailMessage)
                ->subject('Admin OTP Verification')
                //->salutation(' ')
                ->markdown('mail.judges.send-otp-code', [
                    'otpCode' => $this->otpCode,
                ]);
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

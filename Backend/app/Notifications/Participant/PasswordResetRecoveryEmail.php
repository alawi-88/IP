<?php

namespace App\Notifications\Participant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetRecoveryEmail extends Notification
{
    use Queueable;

    public string $resetCode;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $resetCode)
    {
        $this->resetCode = $resetCode;
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
        return (new MailMessage)
                    ->subject('Password Reset Code - Recovery Email')
                    ->greeting('Hello!')
                    ->line('You have requested a password reset using your recovery email.')
                    ->line('Your password reset code is: **' . $this->resetCode . '**')
                    ->line('Please use this code to reset your password.')
                    ->line('This code will expire in 15 minutes.')
                    ->line('If you did not request this password reset, please ignore this email.')
                   ;
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

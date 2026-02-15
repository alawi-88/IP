<?php

namespace App\Notifications\Participant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecoveryEmailOtpVerification extends Notification
{
    use Queueable;

    public string $otpCode;
    public string $recoveryEmail;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otpCode, string $recoveryEmail)
    {
        $this->otpCode = $otpCode;
        $this->recoveryEmail = $recoveryEmail;
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
                    ->subject(__('recovery_email.email_subject'))
                    ->greeting(__('recovery_email.greeting'))
                    ->line(__('recovery_email.adding_recovery_email'))
                    ->line(__('recovery_email.verification_code', ['code' => $this->otpCode]))
                    ->line(__('recovery_email.confirm_email', ['email' => $this->recoveryEmail]))
                    ->line(__('recovery_email.code_expires'))
                    ->line(__('recovery_email.ignore_if_not_requested'));
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

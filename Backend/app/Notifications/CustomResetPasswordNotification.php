<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends Notification
{
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('🔐 Reset Your Password - '.config('app.name').' Platform')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('We received a request to reset your password for your '.config('app.name').' account.')
            ->action('Reset My Password', $url)
            ->line('⚠️ This password reset link will expire in 60 minutes.')
            ->line('If you did not request this, please ignore this email.');
    }
}

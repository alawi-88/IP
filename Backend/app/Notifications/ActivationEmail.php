<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use App\Models\EmailTemplate;
use App\Traits\HasEmailTemplate;
class ActivationEmail extends VerifyEmail
{
    use Queueable, HasEmailTemplate;

    /**
     * Build the e-mail representation of the notification.
     */
    // public function toMail($notifiable): MailMessage
    // {
    //     $url = $this->verificationUrl($notifiable);

    //     $name = $notifiable->name;

    //     return (new MailMessage)
    //         ->subject(__('auth.activation_subject'))
    //         ->greeting(__('auth.activation_greeting', ['name' => $name]))
    //         ->line(__('auth.activation_intro'))
    //         ->action(__('auth.activate_account'), $url)
    //         ->line(__('auth.activation_copy_link', ['url' => $url]))
    //         ->line(__('auth.activation_expiry'))
    //         ->line(__('auth.activation_ignore'))
    //         ->line(__('auth.activation_signoff', ['app' => config('app.name')]));
    // }
    public function toMail($notifiable): MailMessage
{
            $url  = $this->verificationUrl($notifiable);
            //$lang = app()->getLocale(); // or $notifiable->lang
            //$template = EmailTemplate::where('key', 'judge.signup_confirmation')->first();
            $data = $this->renderEmailTemplate('judge.signup_confirmation', [    'name' => $notifiable->name, 'url' => $url,]);
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
                        ->subject(__('auth.activation_subject', ['app' => config('app.name')]))
                        //->salutation(' ')
                        ->greeting(__('auth.activation_greeting', ['name' => $notifiable->name]))
                        ->line(__('auth.activation_intro', ['app' => config('app.name')]))
                        ->action(__('auth.activate_account'), $url)
                        ->line(__('auth.activation_copy_link', ['url' => $url]))
                        ->line(__('auth.activation_expiry'))
                        ->line(__('auth.activation_ignore'))
                        ->line(__('auth.activation_signoff', ['app' => config('app.name')]));

            }
        }

    /**
     * Create a 24-hour signed verification URL.
     */
    protected function verificationUrl($notifiable)
    {
        if (!$notifiable->activation_code) {
            throw new \RuntimeException('Activation code not found for judge');
        }

        return config('app.url') . '/ar/judge/register/verify?' . http_build_query([
            'activation_code' => $notifiable->activation_code,
        ]);
    }
}

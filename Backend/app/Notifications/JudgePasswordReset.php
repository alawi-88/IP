<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EmailTemplate;
use App\Traits\HasEmailTemplate;
class JudgePasswordReset extends Notification
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->renderEmailTemplate('judge.forgot_password', ['code' => $this->code, 'name' => $notifiable->name]);
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
            ->subject(__('passwords.reset_password'))
            ->greeting(' ')
            //->salutation(' ')
            ->line(__('passwords.reset_password_message'))
            ->line(__('passwords.reset_password_code', ['code' => $this->code]));
        }
        
    }
    public function toMail5555(object $notifiable): MailMessage
{
        // Fetch template from DB (if exists)
        $template = EmailTemplate::where('key', 'judge.forgot_password')->first();

        if ($template) {
            $body = $template->body;
            $subject = $template->subject;
        } else {
            // Fallback body with placeholders
            $subject = __('passwords.reset_password');
            $body = "
                <p>".__('passwords.reset_password_message')."</p>
                <p>".__('passwords.reset_password_code', ['code' => '{{code}}'])."</p>
            ";
        }

        // Variables to replace
        $vars = [
            'code' => $this->code,
        ];

        foreach ($vars as $key => $value) {
            $body = str_replace('{{'.$key.'}}', $value, $body);
            $subject = str_replace('{{'.$key.'}}', $value, $subject);
        }

        return (new MailMessage)
            //->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
            ->greeting(' ')
            ->salutation(' ')
            ->subject($subject)
            ->line(new \Illuminate\Support\HtmlString($body));
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

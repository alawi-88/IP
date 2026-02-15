<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
class NewJudgeAccount extends Notification
{
    use Queueable, HasEmailTemplate;

    /**
     * Create a new notification instance.
     */
    public function __construct(private $model, private $password)
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
        $locale  = app()->getLocale();

        $name = is_array($notifiable->name)
            ? ($notifiable->name[$locale] ?? reset($notifiable->name))
            : $notifiable->name;

        $loginUrl = config('app.url') . '/' . app()->getLocale() . '/judge/login';
        $data = $this->renderEmailTemplate('judge.credentials', ['name' => $name, 'email' => $notifiable->email, 'password' => $this->password, 'loginUrl' => $loginUrl]);
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
                ->subject('New Judge Account')
                ->greeting('Hello ' . $this->model->name)
                ->line('Your account has been created successfully with the following details:')
                ->line('Email: ' . $this->model->email)
                ->line('Password: ' . $this->password)
                ->line('You can login to your account using the email and password provided above.')
                ->line('Login URL: ' . config('app.url') . '/' . app()->getLocale() . '/judge/login')
              ;
            }
        
        
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}

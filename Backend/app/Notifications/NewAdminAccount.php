<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EmailTemplate;
use App\Traits\HasEmailTemplate;
class NewAdminAccount extends Notification
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
        $lang = app()->getLocale();
        //$template = EmailTemplate::where('key', 'admin.credentials')->first();

        $data = $this->renderEmailTemplate('admin.credentials', ['name' => $this->model->name, 'role' => $this->model->roles_list ?: '-', 'email' => $this->model->email, 'password' => $this->password, 'loginUrl' => config('app.url').'/admin/login']);
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
                ->subject('New Admin Account')
                ->markdown('mail.admins.new-admin-account', [
                    'admin' => $this->model,
                    'password' => $this->password,
                ]);
        }
       /* $subject = $template?->subject[$lang] ?? 'New Admin Account';
        $body = $template?->body[$lang] ?? 'New Admin Account';

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.admins.new-admin-account', [
                'admin' => $this->model,
                'password' => $this->password,
            ]);*/
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

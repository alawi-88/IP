<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
class UpdateAdminAccount extends Notification
{
    use Queueable;
    use HasEmailTemplate;

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
        $data = $this->renderEmailTemplate('admin.update_admin_account', ['name' => $this->model->name, 'email' => $this->model->email, 'role' => $this->model->roles_list ?: '-', 'password' => $this->password, 'loginUrl' => config('app.url').'/admin/login']);
        if ($data && !empty($data['body']) && $data['body'] !== null) {
            $body = $data['body'];
            $subject = $data['subject'];
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->greeting(' ')
                ->salutation(' ')
                ->subject($subject)
                ->line(new \Illuminate\Support\HtmlString($body));
        }
        else {
        return (new MailMessage)
            ->markdown('mail.admins.update-admin-account', [
                    'admin' => $this->model,
                    'password' => $this->password,
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
            //
        ];
    }
}

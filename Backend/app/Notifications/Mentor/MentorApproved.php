<?php

namespace App\Notifications\Mentor;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;

class MentorApproved extends Notification
{
    use Queueable, HasEmailTemplate;

    public function __construct(private $mentor)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->renderEmailTemplate('mentor.approved', [
            'name' => $this->mentor->name,
            'email' => $this->mentor->email,
            'approved_at' => $this->mentor->approved_at?->format('Y-m-d H:i:s')
        ]);
        
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
                ->greeting(' ')
                //->salutation(' ')
                ->subject(__('mentor.approved_subject'))
                ->line(__('mentor.approved_message', ['name' => $this->mentor->name]))
                ->line(__('mentor.approved_details'));
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => __('mentor.approved_details'),
            'mentor_id' => $this->mentor->id,
            'mentor_name' => $this->mentor->name,
            'mentor_email' => $this->mentor->email,
            'approved_at' => $this->mentor->approved_at?->format('Y-m-d H:i:s'),
        ];
    }
}

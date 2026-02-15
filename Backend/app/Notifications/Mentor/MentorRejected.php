<?php

namespace App\Notifications\Mentor;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;

class MentorRejected extends Notification
{
    use Queueable, HasEmailTemplate;

    public function __construct(private $mentor, private $reason = null)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->renderEmailTemplate('mentor.rejected', [
            'name' => $this->mentor->name,
            'email' => $this->mentor->email,
            'reason' => $this->reason,
            'rejected_at' => $this->mentor->rejected_at?->format('Y-m-d H:i:s')
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
                ->subject(__('mentor.rejected_subject'))
                ->line(__('mentor.rejected_message', ['name' => $this->mentor->name]))
                ->line(__('mentor.rejected_details'));
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'mentor_rejected',
            'mentor_id' => $this->mentor->id,
            'mentor_name' => $this->mentor->name,
            'mentor_email' => $this->mentor->email,
            'rejection_reason' => $this->reason,
            'rejected_at' => $this->mentor->rejected_at?->format('Y-m-d H:i:s'),
        ];
    }
}

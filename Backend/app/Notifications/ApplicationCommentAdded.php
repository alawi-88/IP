<?php

namespace App\Notifications;

use App\Models\ApplicationComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
use App\Traits\HasNotificationMessage;
class ApplicationCommentAdded extends Notification implements ShouldQueue
{
    use Queueable;
    use HasEmailTemplate, HasNotificationMessage;
    public function __construct(public ApplicationComment $comment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $competitionName = $this->comment->application->competition->title ?? 'Competition';
        //$data = null;

        // Determine the commenter name based on who is being notified
        if ($this->comment->user_id && !$this->comment->author_type) {
            // Admin comment - show admin name to participant
            $commenterName = $this->comment->user?->name ?? __('An admin');

        } else {
            // Participant comment - show participant name to admin
            $commenterName = $this->comment->author?->name ?? __('A participant');
        }

        $commenterName =  __('An admin');
        $data_email = $this->renderEmailTemplate('user.application_comment_added', ['competition' => $competitionName, 'NotifiableName' => $notifiable->name, 'commenterName'=> $commenterName, 'comment' => $this->comment->comment]);

        if ($data_email && $data_email['body'] && $data_email['subject']) {
            $body = $data_email['body'];
            $subject = $data_email['subject'];
            $greeting = ' ';
            return (new MailMessage)
                ->subject($subject)
                ->greeting($greeting)
                ->salutation(' ')
                ->line(new \Illuminate\Support\HtmlString($body));

        } else {
            return (new MailMessage)
            ->subject("New comment on your application: {$competitionName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$commenterName} added a new comment on your application:")
            ->line("\"{$this->comment->comment}\"")
            ->line('Please log in to reply.');
        }


    }

    public function toDatabase(object $notifiable): array
    {
        $competitionName = $this->comment->application->competition->title ?? 'Competition';

        // Determine the commenter name based on who is being notified
        if ($this->comment->user_id && !$this->comment->author_type) {
            // Admin comment - show admin name to participant
            $commenterName = $this->comment->user?->name ?? __('An admin');
        } else {
            // Participant comment - show participant name to admin
            $commenterName = $this->comment->author?->name ?? __('A participant');
        }

        $data = $this->renderNotificationMessage('user.application_comment_added', ['competition' => $competitionName, 'admin' => $commenterName, 'comment' => $this->comment->comment]);
        if ($data) {
            $body = $data['body'];
            $subject = $data['subject'];
        } else {
            $subject = "New comment on your application: {$competitionName}";
            $body = "{$commenterName} added a new comment on your application: \"{$this->comment->comment}\"";
        }
        return [
            'title' => $subject,
            'body' => $body,
            'comment_id' => $this->comment->id,
            'application_id' => $this->comment->application_id,
            'admin_name' => $commenterName,
            'competition_name' => $competitionName,
        ];
    }
}

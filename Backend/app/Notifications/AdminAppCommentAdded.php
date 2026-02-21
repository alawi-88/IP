<?php

namespace App\Notifications;

use App\Models\ApplicationComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
use App\Traits\HasNotificationMessage;
class AdminAppCommentAdded extends Notification implements ShouldQueue
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
        $programName = $this->comment->application->program->title ?? 'Program';
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
        $data_email = $this->renderEmailTemplate('user.application_comment_added', ['program' => $programName, 'NotifiableName' => $notifiable->name, 'commenterName'=> $commenterName, 'comment' => $this->comment->comment]);

        $body = $data_email['body'];
        $subject = $data_email['subject'];
        if($data_email['attachments']){
            $relativePath = str_replace('/storage/', '', parse_url($data_email['attachments'][0], PHP_URL_PATH));
            $storagePath = storage_path('app/public/' . $relativePath);
            $body .= '<br><br><a href="' . $storagePath . '">View Attachment</a>';
        }
        $greeting = ' ';
        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            //->salutation(' ')
            ->attach($storagePath)
            ->line(new \Illuminate\Support\HtmlString($body));

        // if ($data_email) {
        //     $body = $data_email['body'];
        //     $subject = $data_email['subject'];
        //     $greeting = ' ';
        //     return (new MailMessage)
        //         ->subject($subject)
        //         ->greeting($greeting)
        //         ->salutation(' ')
        //         ->line(new \Illuminate\Support\HtmlString($body));

        // } else {
        //     return (new MailMessage)
        //     ->subject("New comment on your application: {$programName}")
        //     ->greeting("Hello {$notifiable->name},")
        //     ->line("{$commenterName} added a new comment on your application:")
        //     ->line("\"{$this->comment->comment}\"")
        //     ->line('Please log in to reply.')
        //     ->salutation('Regards, Innovation');
        // }

        
    }

    public function toDatabase(object $notifiable): array
    {
        $programName = $this->comment->application->program->title ?? 'Program';
        
        // Determine the commenter name based on who is being notified
        if ($this->comment->user_id && !$this->comment->author_type) {
            // Admin comment - show admin name to participant
            $commenterName = $this->comment->user?->name ?? __('An admin');
        } else {
            // Participant comment - show participant name to admin
            $commenterName = $this->comment->author?->name ?? __('A participant');
        }

        $data = $this->renderNotificationMessage('user.application_comment_added', ['program' => $programName, 'admin' => $commenterName, 'comment' => $this->comment->comment]);
        if ($data) {
            $body = $data['body'];
            $subject = $data['subject'];
        } else {
            $subject = "New comment on your application: {$programName}";
            $body = "{$commenterName} added a new comment on your application: \"{$this->comment->comment}\"";
        }
        return [
            'title' => $subject,
            'body' => $body,
            'comment_id' => $this->comment->id,
            'application_id' => $this->comment->application_id,
            'admin_name' => $commenterName,
            'program_name' => $programName,
        ];
    }
}

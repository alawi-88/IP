<?php

namespace App\Notifications;

use App\Models\ApplicationComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
use App\Traits\HasNotificationMessage;
class ParticipantApplicationReply extends Notification implements ShouldQueue
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
        $participantName = $this->comment->author?->name ?? __('A participant');

        $data = $this->renderEmailTemplate('admin.participant_application_reply', ['program' => $programName, 'AdminName' => $notifiable->name, 'UserName' => $participantName, 'comment' => $this->comment->comment]);
        if ($data && !empty($data['body']) && $data['body'] !== null) {
            $body = $data['body'];
            $subject = $data['subject'];
            $greeting = ' ';
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->greeting($greeting)
                ->salutation(' ')
                ->subject($subject)
                ->line(new \Illuminate\Support\HtmlString($body));
        } else {
            return (new MailMessage)
            ->subject("New reply on application: {$programName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$participantName} replied to your comment on their application:")
            ->line("\"{$this->comment->comment}\"")
            //->action('View Application', url("/admin/applications/{$this->comment->application_id}"))
            ->line('Please review the reply.');
        }
        
    }

    public function toDatabase(object $notifiable): array
    {
        $programName = optional($this->comment->application)->program->title ?? 'Program'; 
        $participantName = optional($this->comment->author)->name ?? __('A participant');
        $data = $this->renderNotificationMessage('admin.participant_application_reply', ['program' => $programName, 'AdminName' => $notifiable->name, 'UserName' => $participantName, 'comment' => $this->comment->comment]);
        if ($data) {
            $body = $data['body'];
            $subject = $data['subject'];
        } else {
            $subject = "New reply on application: {$programName}";
            $body = "{$participantName} replied to your comment on their application: \"{$this->comment->comment}\"";
        }
        return [
            'title' => $subject,
            'body' => $body,
            //'action_url' => "/admin/applications/{$this->comment->application_id}",
            //'action_text' => 'View Application',
            'comment_id' => $this->comment->id,
            'application_id' => $this->comment->application_id,
            'participant_name' => $participantName,
            'program_name' => $programName,
        ];
    }
}

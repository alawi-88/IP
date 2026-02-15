<?php

namespace App\Notifications;

use App\Models\ProjectComment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
use App\Traits\HasNotificationMessage;
class ProjectCommentAdded extends Notification
{
    use HasEmailTemplate, HasNotificationMessage;

    public function __construct(public ProjectComment $comment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projectName = $this->comment->project->form_submissions['project_name'] ?? 'Project';
        $data = null;
        // Determine the commenter name based on who is being notified
        if ($this->comment->user_id && !$this->comment->author_type) {
            // Admin comment - show admin name to participant
            $commenterName = $this->comment->user?->name ?? __('An admin');
            $data = $this->renderEmailTemplate('user.project_comment_added', ['UserName'=> $notifiable->name, 'project' => $projectName, 'AdminName' => $commenterName, 'comment' => $this->comment->comment]);
        } else {
            // Participant comment - show participant name to admin
            $commenterName = $this->comment->author?->name ?? __('A participant');
            $data = $this->renderEmailTemplate('admin.participant_project_reply', ['project' => $projectName, 'AdminName' => $notifiable->name, 'UserName'=> $commenterName, 'comment' => $this->comment->comment]);
        }
        
        if ($data && !empty($data['body']) && $data['body'] !== null) {
            $body = $data['body'];
            $subject = $data['subject'];
            $greeting = ' ';
            return (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->subject($subject)
                ->greeting($greeting)
                ->salutation(' ')
                ->line(new \Illuminate\Support\HtmlString($body));

        } else {
            return (new MailMessage)
            ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
            ->subject("New comment on your project: {$projectName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$commenterName} added a new comment on your project:")
            ->line("\"{$this->comment->comment}\"")
            ->line('Please log in to reply.');
        }

    }

    public function toDatabase(object $notifiable): array
    {
        $projectName = $this->comment->project->form_submissions['project_name'] ?? 'Project';
        
        // Determine the commenter name based on who is being notified
        if ($this->comment->user_id && !$this->comment->author_type) {
            // Admin comment - show admin name to participant
            $commenterName = $this->comment->user?->name ?? __('An admin');
        } else {
            // Participant comment - show participant name to admin
            $commenterName = $this->comment->author?->name ?? __('A participant');
        }
        
        $data = $this->renderNotificationMessage('user.project_comment_added', ['project' => $projectName, 'admin' => $commenterName, 'comment' => $this->comment->comment]);
        if ($data) {
            $body = $data['body'];
            $subject = $data['subject'];
        } else {
            $subject = "New comment on your project: {$projectName}";
            $body = "{$commenterName} added a new comment on your project: \"{$this->comment->comment}\"";
        }

        $notificationData = [
            'title' => $subject,
            'body' => $body,
            'action_text' => 'View Project',
            'comment_id' => $this->comment->id,
            'project_id' => $this->comment->project_id,
            'admin_name' => $commenterName,
            'project_name' => $projectName,
        ];

        return $notificationData;
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EmailTemplate;
use App\Traits\HasEmailTemplate;
use App\Traits\HasNotificationMessage;
/**
 * Notification sent when a participant submits a project
 * 
 * Usage example:
 * $participant->notify(new ProjectSubmitted($project));
 * 
 * This notification supports both email and database channels
 * and provides content in both Arabic and English based on the current locale.
 */
class ProjectSubmitted extends Notification
{
    use Queueable, HasEmailTemplate, HasNotificationMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected $project)
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     $program = $this->project->form->program;
    //     $programName = $program->title;

    //     return (new MailMessage)
    //         ->subject(__('project_submitted.Project Submitted Successfully'))
    //         ->greeting(__('project_submitted.Dear Participant,'))
    //         ->line(__('project_submitted.project_submitted_message', [
    //             'program' => $programName
    //         ]))
    //         ->line(__('project_submitted.review_process_message'))
    //         ->line(__('project_submitted.Innovation Platform Team'));
    // }
    public function toMail(object $notifiable): MailMessage
    {
        $program = $this->project->form->program;
        $programName = $program->title;
        $data = $this->renderEmailTemplate('user.project_submitted', ['program' => $programName, 'name' => $notifiable->name]);
        
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
            ->subject(__('project_submitted.Project Submitted Successfully'))
            //->salutation(' ')
            ->greeting(__('project_submitted.Dear Participant,'))
            ->line(__('project_submitted.project_submitted_message', [
                'program' => $programName
            ]))
            ->line(__('project_submitted.review_process_message'))
            ->line(__('project_submitted.Innovation Platform Team', [
                'app' => config('app.name'),
            ]));

            
        }
    
        
    }
    
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $program = $this->project->form->program;
        $programName = $program->title;
        $data = $this->renderNotificationMessage('user.project_submitted', ['program' => $programName]);
        
        if ($data) {
            $subject = [
                'ar' => $data->subject['ar'],
                'en' => $data->subject['en'],
            ];
            $body = [
                'ar' => $data->body['ar'],
                'en' => $data->body['en'],
            ];
        } else {
            $subject = getMultilingualTranslation('project_submitted.Project Submitted Successfully');
            $body = [
                'ar' => getTranslationForLocale('project_submitted.project_submitted_message', 'ar', [
                    'program' => $programName
                ]) . ' ' . getTranslationForLocale('project_submitted.review_process_message', 'ar'),
                'en' => getTranslationForLocale('project_submitted.project_submitted_message', 'en', [
                    'program' => $programName
                ]) . ' ' . getTranslationForLocale('project_submitted.review_process_message', 'en')
            ];
        }
        return [
            'project_id' => $this->project->id,
            'program_id' => $this->project->form->program_id,
            'program_name' => $programName,
            'title' => $subject,
            'body' => $body,
            'status' => 'submitted',
        ];
    }
}

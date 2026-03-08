<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasNotificationMessage;
use App\Traits\HasEmailTemplate;
/**
 * Notification sent when a participant's project status is updated
 *
 * Usage example:
 * $participant->notify(new ProjectStatusUpdated($project, $oldStatus, $newStatus));
 *
 * This notification supports both email and database channels
 * and provides content in both Arabic and English based on the current locale.
 */
class ProjectStatusUpdated extends Notification
{
    use Queueable, HasNotificationMessage, HasEmailTemplate;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected $project,
        protected $oldStatus,
        protected $newStatus
    ) {
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
    public function toMail(object $notifiable): MailMessage
    {
        $program = $this->project->form->program;
        $programName = $program->title;
        $projectTitle = $this->project?->form_submissions?->project_name ?? __('project_status.Untitled Project');
        $data = $this->renderEmailTemplate('user.project_status_updates', [
            'project' => $projectTitle,
            'program' => $programName,
            'oldStatus' => __("project_status.statuses.{$this->oldStatus}"),
            'newStatus' => __("project_status.statuses.{$this->newStatus}")
        ]);
        if ($data && !empty($data['body']) && $data['body'] !== null) {
            $body = $data['body'];
            $subject = $data['subject'];
            $greeting = ' ';
            return (new MailMessage)
                ->subject($subject)
                ->greeting($greeting)
                ->salutation(' ')
                ->line(new \Illuminate\Support\HtmlString($body));
        }
        else {
            
            return (new MailMessage)
            ->subject(__('project_status.Project Status Updated'))
            ->greeting(__('project_status.Dear Participant,'))
            //->salutation(' ')
            ->line(__('project_status.status_updated_message', [
                'project' => $projectTitle,
                'program' => $programName,
                'old_status' => __("project_status.statuses.{$this->oldStatus}"),
                'new_status' => __("project_status.statuses.{$this->newStatus}")
            ]))
            ->line(__('project_status.check_status_message'))
            ->line(__('project_status.Innovation Platform Team', [
                'app' => config('app.name'),
            ])
        );
        }
        
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $program = $this->project?->form?->program;
        $programName = $program->title;
        $projectTitle = $this->project?->form_submissions?->project_name ?? getTranslationForLocale('project_status.Untitled Project', app()->getLocale());

        $data = $this->renderNotificationMessage('user.project_status_updates', [
            'project' => $projectTitle,
            'program' => $programName,
            'old_status' => getTranslationForLocale("project_status.statuses.{$this->oldStatus}", 'ar'),
            'new_status' => getTranslationForLocale("project_status.statuses.{$this->newStatus}", 'ar')
        ]);
        $data_en = $this->renderNotificationMessage('user.project_status_updates', [
            'project' => $projectTitle,
            'program' => $programName,
            'old_status' => getTranslationForLocale("project_status.statuses.{$this->oldStatus}", 'en'),
            'new_status' => getTranslationForLocale("project_status.statuses.{$this->newStatus}", 'en')
        ]);
        if ($data) {
            $body = [
                'ar' => $data->body['ar'],
                'en' => $data_en->body['en'],
            ];
            $subject = [
                'ar' => $data->subject['ar'],
                'en' => $data_en->subject['en'],
                ];
        } else {
            $subject = __('project_status.Project Status Updated');
            $body = [
                'ar' => getTranslationForLocale('project_status.status_updated_message', 'ar', [
                    'project' => $projectTitle,
                    'program' => $programName,
                    'old_status' => getTranslationForLocale("project_status.statuses.{$this->oldStatus}", 'ar'),
                    'new_status' => getTranslationForLocale("project_status.statuses.{$this->newStatus}", 'ar')
                ]),
                'en' => getTranslationForLocale('project_status.status_updated_message', 'en', [
                    'project' => $projectTitle,
                    'program' => $programName,
                    'old_status' => getTranslationForLocale("project_status.statuses.{$this->oldStatus}", 'en'),
                    'new_status' => getTranslationForLocale("project_status.statuses.{$this->newStatus}", 'en')
                ])
                ];
        }

        return [
            'project_id' => $this->project->id,
            'program_id' => $this->project->form->program_id,
            'program_name' => $programName,
            'title' => $subject,
            'body' => $body,
            'status' => 'updated',
        ];
    }
}

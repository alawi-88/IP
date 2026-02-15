<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasNotificationMessage;
use App\Traits\HasEmailTemplate;

/**
 * Notification sent when project evaluation results are published
 *
 * Usage example:
 * $participant->notify(new ProjectEvaluationResult($project));
 *
 * This notification supports both email and database channels
 * and provides content in both Arabic and English based on the current locale.
 */
class ProjectEvaluationResult extends Notification
{
    use Queueable, HasNotificationMessage, HasEmailTemplate;

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
    public function toMail(object $notifiable): MailMessage
    {
        $competition = $this->project->form->competition;
        $competitionName = $competition->title;
        $projectTitle = $this->project?->form_submissions?->project_name ?? __('project_evaluation.Untitled Project');
        $appName = config('app.name', ' Platform');
        
        $data = $this->renderEmailTemplate('user.project_evaluation', [
            'name' => is_array($notifiable->name) ? ($notifiable->name[app()->getLocale()] ?? $notifiable->name['en'] ?? '') : ($notifiable->name ?? ''),
            'appName' => $appName,
            'project' => $projectTitle,
            'competition' => $competitionName,
        ]);
        
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
                ->subject(__('project_evaluation.Project Evaluation Results'))
                ->greeting(__('project_evaluation.Dear Participant,'))
                ->line(__('project_evaluation.evaluation_results_message', [
                    'project' => $projectTitle,
                    'competition' => $competitionName,
                ]))
                ->line(__('project_evaluation.check_results_message'))
                ->line(__('project_evaluation.Innovation Platform Team', [
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
        $competition = $this->project->form->competition;
        $competitionName = $competition->title;
        $projectTitle = $this->project?->form_submissions?->project_name ?? getTranslationForLocale('project_evaluation.Untitled Project', app()->getLocale());
        $appName = config('app.name', ' Platform');
        $participantName = is_array($notifiable->name) ? ($notifiable->name['ar'] ?? $notifiable->name['en'] ?? '') : ($notifiable->name ?? '');

        $data = $this->renderNotificationMessage('user.project_evaluation', [
            'name' => $participantName,
            'appName' => $appName,
            'project' => $projectTitle,
            'competition' => $competitionName,
        ]);
        
        $data_en = $this->renderNotificationMessage('user.project_evaluation', [
            'name' => is_array($notifiable->name) ? ($notifiable->name['en'] ?? '') : ($notifiable->name ?? ''),
            'appName' => $appName,
            'project' => $projectTitle,
            'competition' => $competitionName,
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
            $subject = __('project_evaluation.Project Evaluation Results');
            $body = [
                'ar' => getTranslationForLocale('project_evaluation.evaluation_results_message', 'ar', [
                    'project' => $projectTitle,
                    'competition' => $competitionName,
                ]),
                'en' => getTranslationForLocale('project_evaluation.evaluation_results_message', 'en', [
                    'project' => $projectTitle,
                    'competition' => $competitionName,
                ])
            ];
        }

        return [
            'project_id' => $this->project->id,
            'competition_id' => $this->project->form->competition_id,
            'competition_name' => $competitionName,
            'title' => $subject,
            'body' => $body,
            'status' => 'evaluation_completed',
        ];
    }
}


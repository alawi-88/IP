<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
use App\Traits\HasNotificationMessage;
/**
 * Notification sent when a participant's application status is updated
 * 
 * Usage example:
 * $participant->notify(new ApplicationStatusUpdated($application, $oldStatus, $newStatus));
 * 
 * This notification supports both email and database channels
 * and provides content in both Arabic and English based on the current locale.
 */
class ApplicationStatusUpdated extends Notification
{
    use Queueable, HasEmailTemplate, HasNotificationMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected $application,
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
        $program = $this->application->program;
        $programName = $program->title;

        $data = $this->renderEmailTemplate('user.screening_result', [
            'program' => $programName,
            'old_status' => __("application_status.statuses.{$this->oldStatus}"),
            'new_status' => __("application_status.statuses.{$this->newStatus}")
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
        }else{

        return (new MailMessage)
            ->subject(__('application_status.Application Status Updated'))
            ->greeting(__('application_status.Dear Participant,'))
            ->line(__('application_status.status_updated_message', [
                'program' => $programName,
                'old_status' => __("application_status.statuses.{$this->oldStatus}"),
                'new_status' => __("application_status.statuses.{$this->newStatus}")
            ]))
            ->line(__('application_status.check_status_message'))
            ->line(config('app.name') . ' Team');
        }
    }

    public function toMail5555(object $notifiable): MailMessage
    {
        $greeting = __('application_status.Dear Participant,');
        $program = $this->application->program;
        $programName = $program->title;
        $data = $this->renderEmailTemplate('user.screening_result', [
            'program' => $programName,
            'old_status' => __("application_status.statuses.{$this->oldStatus}"),
            'new_status' => __("application_status.statuses.{$this->newStatus}")
        ]);
        if ($data) {
            $body = $data['body'];
            $subject = $data['subject'];
            $greeting = ' ';
        } else {
            $subject = __('application_status.Application Status Updated');
    
            $body = "
                <p>".__('application_status.Dear Participant,')."</p>
                <p>".__('application_status.status_updated_message', [
                    'program' => '{{program}}',
                    'old_status' => '{{old_status}}',
                    'new_status' => '{{new_status}}'
                ])."</p>
                <p>".__('application_status.check_status_message')."</p>
                <p>".__('application_status.Innovation Platform Team', [
                    'app' => config('app.name'),
                ])."</p>
            ";

            $vars = [
                'program' => $programName,
                'old_status' => __("application_status.statuses.{$this->oldStatus}"),
                'new_status' => __("application_status.statuses.{$this->newStatus}")
            ];
        
            foreach ($vars as $key => $value) {
                $body = str_replace('{{'.$key.'}}', $value, $body);
                $subject = str_replace('{{'.$key.'}}', $value, $subject);
            }
        }
       

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            //->salutation(' ')
            ->line(new \Illuminate\Support\HtmlString($body));
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray555(object $notifiable): array
    {
        return [
            'title' => getMultilingualTranslation('application_status.Application Status Updated'),
            'body' => [
                'ar' => getTranslationForLocale('application_status.status_updated_message', 'ar', [
                    'program' => $this->application->program?->title,
                    'old_status' => getTranslationForLocale("application_status.statuses.{$this->oldStatus}", 'ar'),
                    'new_status' => getTranslationForLocale("application_status.statuses.{$this->newStatus}", 'ar')
                ]),
                'en' => getTranslationForLocale('application_status.status_updated_message', 'en', [
                    'program' => $this->application->program?->title,
                    'old_status' => getTranslationForLocale("application_status.statuses.{$this->oldStatus}", 'en'),
                    'new_status' => getTranslationForLocale("application_status.statuses.{$this->newStatus}", 'en')
                ])
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
            $data = $this->renderNotificationMessage('user.application_status_updates', [
                'program' => $this->application->program?->title,
                'old_status' => getTranslationForLocale("application_status.statuses.{$this->oldStatus}", 'ar'),
                'new_status' => getTranslationForLocale("application_status.statuses.{$this->newStatus}", 'ar'),
            ]);
            $data_en = $this->renderNotificationMessage('user.application_status_updates', [
                'program' => $this->application->program?->title,
                'old_status' => getTranslationForLocale("application_status.statuses.{$this->oldStatus}", 'en'),
                'new_status' => getTranslationForLocale("application_status.statuses.{$this->newStatus}", 'en')
            ]);
        if ($data) {
            //$subject = $data->subject;
            return [
                'title' => [
                    'ar' => $data->subject['ar'],
                    'en' => $data_en->subject['en']
                ],
                'body' => [
                    'ar' => $data->body['ar'],
                    'en' => $data_en->body['en']
                ],
            ];
        } else {
            return [
                'title' => getMultilingualTranslation('application_status.Application Status Updated'),
                'body' => [
                    'ar' => getTranslationForLocale('application_status.status_updated_message', 'ar', [
                        'program' => $this->application->program?->title,
                        'old_status' => getTranslationForLocale("application_status.statuses.{$this->oldStatus}", 'ar'),
                        'new_status' => getTranslationForLocale("application_status.statuses.{$this->newStatus}", 'ar')
                    ]),
                    'en' => getTranslationForLocale('application_status.status_updated_message', 'en', [
                        'program' => $this->application->program?->title,
                        'old_status' => getTranslationForLocale("application_status.statuses.{$this->oldStatus}", 'en'),
                        'new_status' => getTranslationForLocale("application_status.statuses.{$this->newStatus}", 'en')
                    ])
                ],
            ];
        }
        
    }

    /**
     * Get status translations for both locales
     */
    private function getStatusTranslations(string $status): array
    {
        return getMultilingualTranslation("application_status.statuses.{$status}");
    }
}

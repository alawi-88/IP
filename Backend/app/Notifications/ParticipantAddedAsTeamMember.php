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
 * Notification sent when a participant is added to a team
 * 
 * Usage example:
 * $participant->notify(new ParticipantAddedAsTeamMember($team));
 * 
 * This notification supports both email and database channels
 * and provides content in both Arabic and English based on the current locale.
 */
class ParticipantAddedAsTeamMember extends Notification
{
    use Queueable, HasEmailTemplate, HasNotificationMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected $team)
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
    //     $competition = $this->team->application->competition;
    //     $teamName = $this->team->name;
    //     $competitionName = $competition->title;

    //     return (new MailMessage)
    //         ->subject(__('team_member.You have been added to a team!'))
    //         ->greeting(__('team_member.Dear Participant,'))
    //         ->line(__('team_member.team_added_message', [
    //             'team' => $teamName,
    //             'competition' => $competitionName,
    //         ]))
    //         ->action(__('team_member.View Team'), url('/participant-dashboard/my-competitions'))
    //         ->line(__('team_member.Innovation Platform Team'));
    // }
    public function toMail(object $notifiable): MailMessage
{
    $competition = $this->team->application->competition;
    $teamName = $this->team->name;
    $competitionName = $competition->title;

    // Try to get template from DB
    $data = $this->renderEmailTemplate('user.team_addition', ['url'=>url('/participant-dashboard/my-competitions') , 'team' => $teamName, 'competition' => $competitionName]);
    if ($data && !empty($data['body']) && $data['body'] !== null) {

        $body = $data['body'];
        $subject = $data['subject'];
        return (new MailMessage)
            ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
            ->salutation(' ')
            ->subject($subject)
            ->greeting(' ') 
            ->salutation(' ')
            ->line(new \Illuminate\Support\HtmlString($body));
            
    } else {
        return (new MailMessage)
            ->subject(__('team_member.You have been added to a team!'))
           // ->salutation(' ')
            ->greeting(__('team_member.Dear Participant,'))
            ->line(__('team_member.team_added_message', [
                'team' => $teamName,
                'competition' => $competitionName,
            ]))
            ->action(__('team_member.View Team'), url('/participant-dashboard/my-competitions'))
            ->line(__('team_member.Innovation Platform Team', [
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
        $competition = $this->team->application->competition;
        $teamName = $this->team->name;
        $competitionName = $competition->title;
        $data = $this->renderNotificationMessage('user.team_addition', ['team' => $teamName, 'competition' => $competitionName]);
        if ($data) {
            $body = $data['body'];
            $subject = $data['subject'];
        } else {
            $subject = getMultilingualTranslation('team_member.Added to Team');
            $body =[
                'ar' => getTranslationForLocale('team_member.platform_notification_message', 'ar', [
                    'team' => $teamName,
                    'competition' => $competitionName,
                ]),
                'en' => getTranslationForLocale('team_member.platform_notification_message', 'en', [
                    'team' => $teamName,
                    'competition' => $competitionName,
                ])
                ];
        }
        return [
            'title' => $subject,
            'body' => $body,
            'action_url' => url('/participant-dashboard/my-competitions'),
        ];
    }
}

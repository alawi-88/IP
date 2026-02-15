<?php

namespace App\Notifications\Mentor;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\HasEmailTemplate;
class NewBookingNotification extends Notification implements ShouldQueue
{
    use Queueable, HasEmailTemplate;

    protected MentorSession $session;

    public function __construct(MentorSession $session)
    {
        $this->session = $session;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        // Reload session from database to ensure all data is loaded (important for queued notifications)
        $this->session = $this->session->fresh(['mentor', 'participant', 'competition']);

        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        // Format scheduled_at safely - convert to Asia/Riyadh timezone first
        // Laravel automatically converts scheduled_at from UTC to app timezone (Asia/Riyadh) when reading from DB
        // So scheduled_at is already in Asia/Riyadh timezone, we just need to ensure it's displayed correctly
        $scheduledAtLocal = $this->session->scheduled_at->copy();
        $scheduledDate = 'N/A';
        $scheduledDate2 = $scheduledAtLocal->locale($locale)->translatedFormat('M d, Y');
        $scheduledTime = 'N/A';
        $scheduledTime2 = $scheduledAtLocal->locale($locale)->translatedFormat('g:i A');
        if ($this->session->scheduled_at) {
            try {
                $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon
                    ? $this->session->scheduled_at
                    : \Carbon\Carbon::parse($this->session->scheduled_at);

                if ($scheduledAt) {
                  //  $localizedDate = $scheduledAt->setLocale($locale);
                   // if ($localizedDate) {
                        // Laravel automatically converts scheduled_at from UTC to app timezone (Asia/Riyadh) when reading from DB
                        // So scheduled_at is already in Asia/Riyadh timezone
                        $scheduledAtLocal = $scheduledAt->copy();
                        $scheduledDate = $scheduledAtLocal->format('M d, Y');
                        $scheduledTime = $scheduledAtLocal->format('g:i A');
                   // }
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to format scheduled_at in NewBookingNotification::toMail", [
                    'session_id' => $this->session->id,
                    'scheduled_at' => $this->session->scheduled_at,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Ensure duration is 30 minutes (override if 60 was saved)
        $durationMinutes = $this->session->duration_minutes ?? 30;
        if ($durationMinutes == 60) {
            $durationMinutes = 30; // Override 60 to 30
        }

        $data = $this->renderEmailTemplate('mentor.new_booking_notification', [
            'participant' => $this->session->participant->name ?? '',
            'program' => $this->session->competition->title ?? '',
            'description' => $this->session->description ?? '',
            'date' => $scheduledDate2,
            'time' => $scheduledTime2,
            'duration' => $durationMinutes,
            'id' => $this->session->id,
        ]);

        if ($data && !empty($data['body']) && $data['body'] !== null) {
            $body = $data['body'];
            $subject = $data['subject'];
            $message = (new MailMessage)
            ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->subject($subject)
                ->line(new \Illuminate\Support\HtmlString($body));
            return $message;
        }else{
            $participantName = is_array($this->session->participant->name ?? null)
                ? ($this->session->participant->name['en'] ?? '')
                : ($this->session->participant->name ?? '');

            $competitionTitle = '';
            if ($this->session->relationLoaded('competition') && $this->session->competition) {
                $competitionTitle = is_array($this->session->competition->title ?? null)
                    ? ($this->session->competition->title['en'] ?? '')
                    : ($this->session->competition->title ?? '');
            } elseif (!$this->session->relationLoaded('competition') && $this->session->competition_id) {
                // Load competition if not already loaded
                $competition = \App\Models\Competition::find($this->session->competition_id);
                if ($competition) {
                    $competitionTitle = is_array($competition->title ?? null)
                        ? ($competition->title['en'] ?? '')
                        : ($competition->title ?? '');
                }
            }

            $participantEmail = $this->session->participant->email ?? '';

            $message = (new MailMessage)
                ->theme(config('app.locale') === 'ar' ? 'ar-default' : 'default')
                ->subject(__('notifications.new_booking_subject'))
                ->greeting(__('notifications.hello', ['name' => $notifiable->name['en'] ?? '']))
                ->line(__('notifications.new_booking_message', [
                    'participant_name' => $participantName,
                ]));

            // Display participant information in separate rows
            $message->line(__('notifications.participant_information'))
                    ->line(__('notifications.full_name_label') . ': ' . $participantName)
                    ->line(__('notifications.email_label') . ': ' . $participantEmail);

            $message->line(__('notifications.session_date_label') . ': ' . $scheduledDate2);

            if ($competitionTitle) {
                $message->line(__('notifications.program_label') . ': ' . $competitionTitle);
            }

            // Display session details
            // if ($this->session->description) {
            //     $message->line(__('notifications.session_title_label') . ': ' . $this->session->description);
            // }

            $message->line(__('notifications.session_time_label') . ': ' . $scheduledTime2);

            $message->line(__('notifications.session_duration_label') . ': ' . ($this->session->duration_formatted ?? 'N/A'))
                    ->line(__('notifications.booking_id_label') . ': #' . $this->session->id);

            // Display note/description if available
            if ($this->session->description) {
                $message->line(__('notifications.note_label'))
                    ->line($this->session->description);
            }

            $message->line(__('notifications.new_booking_footer'));
            return $message;
        }


    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        // Reload session from database to ensure all data is loaded (important for queued notifications)
        $this->session = $this->session->fresh(['mentor', 'participant', 'competition']);

        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $participantName = '';
        if ($this->session->participant) {
            $participantName = is_array($this->session->participant->name ?? null)
                ? ($this->session->participant->name[$locale] ?? $this->session->participant->name['en'] ?? '')
                : ($this->session->participant->name ?? '');
        }

        // Laravel automatically converts scheduled_at from UTC to app timezone (Asia/Riyadh) when reading from DB
        // So scheduled_at is already in Asia/Riyadh timezone, we just need to ensure it's displayed correctly
        $scheduledAtLocal = $this->session->scheduled_at->copy();
        $scheduledDate = 'N/A';
        $scheduledDate2 = $scheduledAtLocal->locale($locale)->translatedFormat('M d, Y');
        $scheduledTime = 'N/A';
        $scheduledTime2 = $scheduledAtLocal->locale($locale)->translatedFormat('g:i A');
        if ($this->session->scheduled_at) {
            try {
                $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon
                    ? $this->session->scheduled_at
                    : \Carbon\Carbon::parse($this->session->scheduled_at);

                if ($scheduledAt) {
                    // Laravel automatically converts scheduled_at from UTC to app timezone (Asia/Riyadh) when reading from DB
                    // So scheduled_at is already in Asia/Riyadh timezone
                    $scheduledAtLocal = $scheduledAt->copy();
                    $localizedDate = $scheduledAtLocal->setLocale($locale);
                    if ($localizedDate) {
                        $scheduledDate = $localizedDate->format('M d, Y');
                        $scheduledTime = $localizedDate->format('g:i A');
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to format scheduled_at in NewBookingNotification::toArray", [
                    'session_id' => $this->session->id,
                    'scheduled_at' => $this->session->scheduled_at,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $scheduledAtFormatted = null;
        if ($this->session->scheduled_at) {
            try {
                $scheduledAt = $this->session->scheduled_at instanceof \Carbon\Carbon
                    ? $this->session->scheduled_at
                    : \Carbon\Carbon::parse($this->session->scheduled_at);

                if ($scheduledAt) {
                    $scheduledAtFormatted = $scheduledAt->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to format scheduled_at in NewBookingNotification::toArray", [
                    'session_id' => $this->session->id,
                    'scheduled_at' => $this->session->scheduled_at,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'type' => 'new_booking',
            'session_id' => $this->session->id,
            'title' => $this->session->title ?? null,
            'scheduled_at' => $scheduledAtFormatted,
            'duration_minutes' => $this->session->duration_minutes,
            'participant_name' => $participantName,
            'booking_id' => $this->session->id,
            'message' => __('notifications.new_booking_message', [
                'participant_name' => $participantName,
                'title' => $this->session->title ?? '',
                'date' => $scheduledDate2,
                'time' => $scheduledTime2,
                'duration' => $this->session->duration_formatted ?? 'N/A',
                'booking_id' => $this->session->id,
            ]),
        ];
    }
}


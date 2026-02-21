<?php

namespace App\Notifications\Mentor;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected MentorSession $session;
    protected array $changes;

    public function __construct(MentorSession $session, array $changes = [])
    {
        $this->session = $session;
        $this->changes = $changes;
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
        $this->session = $this->session->fresh(['mentor', 'participant', 'program']);

        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $userName = is_array($notifiable->name ?? null)
            ? ($notifiable->name[$locale] ?? $notifiable->name['en'] ?? '')
            : ($notifiable->name ?? '');

        $message = (new MailMessage)
            ->subject(__('notifications.session_updated_subject'))
            ->greeting(__('notifications.hello', ['name' => $userName]))
            ->line(__('notifications.session_updated_message', [
                'title' => $this->session->title,
            ]));

        // Show session details
        $participantName = null;
        if ($this->session->participant) {
            $participantName = is_array($this->session->participant->name ?? null)
                ? ($this->session->participant->name[$locale] ?? $this->session->participant->name['en'] ?? '')
                : ($this->session->participant->name ?? '');
        }

        if ($participantName) {
            $message->line(__('notifications.participant_information'))
                    ->line(__('notifications.full_name_label') . ': ' . $participantName);
        }

        // Show what changed with old and new values
        if (isset($this->changes['scheduled_at'])) {
            $oldDate = '';
            // Translate "at" based on locale. In Arabic, use "في" instead of "at".
            $atStr = $locale === 'ar' ? 'الساعة' : 'at';
            $oldDate2 = $this->changes['scheduled_at']['old']->locale($locale)->translatedFormat("M d, Y $atStr g:i A");
            if (is_array($this->changes['scheduled_at']) && isset($this->changes['scheduled_at']['old']) && $this->changes['scheduled_at']['old'] !== null) {
                try {
                    $oldValue = $this->changes['scheduled_at']['old'];
                    $parsedDate = $oldValue instanceof \Carbon\Carbon ? $oldValue : \Carbon\Carbon::parse($oldValue);
                    if ($parsedDate !== null && $parsedDate instanceof \Carbon\Carbon) {
                        $localizedDate = $parsedDate->setLocale($locale);
                        if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                            $oldDate = $localizedDate->format("M d, Y $atStr g:i A");
                        }
                    }
                } catch (\Exception $e) {
                    $oldDate = '';
                }
            }
            $newDate = 'N/A';
            $newDateFormatted = 'N/A';
            $newTimeFormatted = 'N/A';
            $newDate2 = $this->session->scheduled_at->locale($locale)->translatedFormat("M d, Y $atStr g:i A");
            // Try to get scheduled_at from changes first (most reliable), then from session
            $scheduledAt = null;
            if (isset($this->changes['scheduled_at']['new'])) {
                $newValue = $this->changes['scheduled_at']['new'];
                try {
                    $scheduledAt = $newValue instanceof \Carbon\Carbon ? $newValue : \Carbon\Carbon::parse($newValue);
                } catch (\Exception $e) {
                    $scheduledAt = null;
                }
            }
            // Fallback to session scheduled_at if not in changes
            if (!$scheduledAt && $this->session->scheduled_at) {
                $scheduledAt = $this->session->scheduled_at;
            }
            if ($scheduledAt) {
                try {
                    $localizedDate = $scheduledAt instanceof \Carbon\Carbon ? $scheduledAt->setLocale($locale) : \Carbon\Carbon::parse($scheduledAt)->setLocale($locale);
                    if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                        $newDate = $localizedDate->format("M d, Y $atStr g:i A");
                        $newDateFormatted = $localizedDate->translatedFormat('M d, Y');
                        $newTimeFormatted = $localizedDate->translatedFormat('g:i A');
                    }
                } catch (\Exception $e) {
                    // Keep default values
                }
            }

            // Use formatted date and time separately for the message
            $messageDate = $newDateFormatted !== 'N/A' ? $newDateFormatted : ($this->session->scheduled_at ? $this->session->scheduled_at->locale($locale)->translatedFormat('M d, Y') : 'N/A');
            $messageTime = $newTimeFormatted !== 'N/A' ? $newTimeFormatted : ($this->session->scheduled_at ? $this->session->scheduled_at->locale($locale)->translatedFormat('g:i A') : 'N/A');

            $message->line(__('notifications.session_time_changed', [
                'new_date' => $messageDate,
                'new_time' => $messageTime,
            ]));

            if ($oldDate2) {
                $message->line(__('notifications.previous_time') . ': ' . $oldDate2)
                        ->line(__('notifications.new_time') . ': ' . $newDate2);
            }
        }

        if (isset($this->changes['duration_minutes'])) {
            $oldDuration = is_array($this->changes['duration_minutes']) && isset($this->changes['duration_minutes']['old'])
                ? $this->formatDuration($this->changes['duration_minutes']['old'], $locale)
                : '';
            $newDuration = $this->session->duration_formatted;

            $message->line(__('notifications.session_duration_changed', [
                'old_duration' => $oldDuration,
                'new_duration' => $newDuration,
            ]));

            if ($oldDuration) {
                $message->line(__('notifications.previous_duration') . ': ' . $oldDuration)
                        ->line(__('notifications.new_duration') . ': ' . $newDuration);
            }
        }

        if (isset($this->changes['title'])) {
            $oldTitle = is_array($this->changes['title']) && isset($this->changes['title']['old'])
                ? $this->changes['title']['old']
                : '';
            $newTitle = $this->session->title;

            $message->line(__('notifications.session_title_changed', [
                'old_title' => $oldTitle,
                'new_title' => $newTitle,
            ]));

            if ($oldTitle) {
                $message->line(__('notifications.previous_title') . ': ' . $oldTitle)
                        ->line(__('notifications.new_title') . ': ' . $newTitle);
            }
        }

        // Show current session details
        $message->line(__('notifications.session_details'));

        // Try to get scheduled_at from changes first (most reliable), then from session
        $scheduledAt = null;
        if (isset($this->changes['scheduled_at']['new'])) {
            $newValue = $this->changes['scheduled_at']['new'];
            try {
                $scheduledAt = $newValue instanceof \Carbon\Carbon ? $newValue : \Carbon\Carbon::parse($newValue);
            } catch (\Exception $e) {
                $scheduledAt = null;
            }
        }
        // Fallback to session scheduled_at if not in changes
        if (!$scheduledAt && $this->session->scheduled_at) {
            $scheduledAt = $this->session->scheduled_at;
        }

        if ($scheduledAt) {
            try {
                $localizedDate = $scheduledAt instanceof \Carbon\Carbon ? $scheduledAt->setLocale($locale) : \Carbon\Carbon::parse($scheduledAt)->setLocale($locale);
                if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                    $message->line(__('notifications.session_date_label') . ': ' . $localizedDate->translatedFormat('M d, Y'))
                            ->line(__('notifications.session_time_label') . ': ' . $localizedDate->translatedFormat('g:i A'));
                }
            } catch (\Exception $e) {
                // Skip date/time display if there's an error
            }
        } else {
            // Fallback: use session scheduled_at if available
            if ($this->session->scheduled_at) {
                try {
                    $localizedDate = $this->session->scheduled_at->setLocale($locale);
                    if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                        $message->line(__('notifications.session_date_label') . ': ' . $localizedDate->translatedFormat('M d, Y'))
                                ->line(__('notifications.session_time_label') . ': ' . $localizedDate->translatedFormat('g:i A'));
                    }
                } catch (\Exception $e) {
                    // Skip date/time display if there's an error
                }
            }
        }

        // Ensure locale is set before accessing duration_formatted attribute
        $originalLocale = app()->getLocale();
        app()->setLocale($locale);
        $durationFormatted = $this->session->duration_formatted;
        app()->setLocale($originalLocale);

        $message->line(__('notifications.session_duration_label') . ': ' . $durationFormatted);

        if ($this->session->join_url) {
            $message->action(
                __('notifications.join_session'),
                $this->session->join_url
            );
        }

        $message->line(__('notifications.session_updated_footer'));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        // Reload session from database to ensure all data is loaded (important for queued notifications)
        $this->session = $this->session->fresh(['mentor', 'participant', 'program']);

        // Set locale based on user's preference (check notification locale property first)
        $locale = getUserPreferredLocale($notifiable, $this);
        app()->setLocale($locale);

        $participantName = null;
        if ($this->session->participant) {
            $participantName = is_array($this->session->participant->name ?? null)
                ? ($this->session->participant->name[$locale] ?? $this->session->participant->name['en'] ?? '')
                : ($this->session->participant->name ?? '');
        }

        // Build detailed message with old and new values
        $messageParts = [__('notifications.session_updated_message', ['title' => $this->session->title])];

        // Translate "at" based on locale. In Arabic, use "في" instead of "at".
        $atStr = $locale === 'ar' ? 'الساعة' : 'at';

        if (isset($this->changes['scheduled_at'])) {
            $oldDate = $oldDate2 = '';
            if (is_array($this->changes['scheduled_at']) && isset($this->changes['scheduled_at']['old']) && $this->changes['scheduled_at']['old'] !== null) {
                try {
                    $oldValue = $this->changes['scheduled_at']['old'];
                    $oldDate2 = $this->changes['scheduled_at']['old']->locale($locale)->translatedFormat("M d, Y $atStr g:i A");
                } catch (\Exception $e) {
                    $oldDate = '';
                }
            }

            $newDate = 'N/A';
            $newDate2 = $this->session->scheduled_at->locale($locale)->translatedFormat("M d, Y $atStr g:i A");
            // Try to get scheduled_at from changes first (most reliable), then from session
            $scheduledAt = null;
            if (isset($this->changes['scheduled_at']['new'])) {
                $newValue = $this->changes['scheduled_at']['new'];
                try {
                    $scheduledAt = $newValue instanceof \Carbon\Carbon ? $newValue : \Carbon\Carbon::parse($newValue);
                } catch (\Exception $e) {
                    $scheduledAt = null;
                }
            }
            // Fallback to session scheduled_at if not in changes
            if (!$scheduledAt && $this->session->scheduled_at) {
                $scheduledAt = $this->session->scheduled_at;
            }
            if ($scheduledAt) {
                try {
                    $localizedDate = $scheduledAt instanceof \Carbon\Carbon ? $scheduledAt->setLocale($locale) : \Carbon\Carbon::parse($scheduledAt)->setLocale($locale);

                    if ($localizedDate !== null && $localizedDate instanceof \Carbon\Carbon) {
                        $newDate = $localizedDate->format("M d, Y $atStr g:i A");
                    }
                } catch (\Exception $e) {
                    $newDate = 'N/A';
                }
            }

            if ($oldDate2) {
                $messageParts[] = __('notifications.previous_time') . ': ' . $oldDate2;
            }
            $messageParts[] = __('notifications.new_time') . ': ' . $newDate2;
        }

        if (isset($this->changes['duration_minutes'])) {
            $oldDuration = is_array($this->changes['duration_minutes']) && isset($this->changes['duration_minutes']['old'])
                ? $this->formatDuration($this->changes['duration_minutes']['old'], $locale)
                : '';
            $newDuration = $this->session->duration_formatted;

            if ($oldDuration) {
                $messageParts[] = __('notifications.previous_duration') . ': ' . $oldDuration;
            }
            $messageParts[] = __('notifications.new_duration') . ': ' . $newDuration;
        }

        return [
            'type' => 'session_updated',
            'session_id' => $this->session->id,
            'title' => $this->session->title,
            'scheduled_at' => $this->session->scheduled_at ? $this->session->scheduled_at->format('Y-m-d H:i:s') : null,
            'duration_minutes' => $this->session->duration_minutes,
            'join_url' => $this->session->join_url,
            'video_tool' => $this->session->video_tool,
            'participant_name' => $participantName,
            'changes' => $this->changes,
            'message' => implode("\n", $messageParts),
        ];
    }

    private function formatDuration(int $minutes, ?string $locale = null): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        // Get locale for translations
        $locale = $locale ?? app()->getLocale();
        $hourUnit = __('notifications.hour', [], $locale);
        $minuteUnit = __('notifications.minute', [], $locale);

        if ($hours > 0) {
            if ($remainingMinutes > 0) {
                return "{$hours}{$hourUnit} {$remainingMinutes}{$minuteUnit}";
            }
            return "{$hours}{$hourUnit}";
        }

        return "{$remainingMinutes}{$minuteUnit}";
    }
}

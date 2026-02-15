<?php

namespace App\Notifications\Mentor;

use App\Models\MentorSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        // Determine locale; mentors store translatable names
        $locale = function_exists('getUserPreferredLocale')
            ? getUserPreferredLocale($notifiable, $this)
            : (app()->getLocale() ?? 'en');

        $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

        $mentorName = is_array($notifiable->name ?? null)
            ? ($notifiable->name[$locale] ?? $notifiable->name['en'] ?? $notifiable->name['ar'] ?? '')
            : ($notifiable->name ?? '');

        $participantName = null;
        if ($this->session->participant) {
            $name = $this->session->participant->name ?? null;
            $participantName = is_array($name)
                ? ($name[$locale] ?? $name['en'] ?? $name['ar'] ?? '')
                : ($name ?? '');
        }

        $scheduledAt = $this->session->scheduled_at;
        $sessionDate = $scheduledAt ? $scheduledAt->format('Y-m-d') : 'N/A';
        $sessionTime = $scheduledAt ? $scheduledAt->format('H:i') : 'N/A';
        $timezone   = config('app.timezone', 'UTC');
        $duration   = $this->session->duration_formatted ?? ($this->session->duration_minutes . ' min');
        $joinLink   = $this->session->join_url ?: config('app.frontend_url');
        $sessionTitle = $this->session->title ?? '';

        $mail = new MailMessage();

        if ($locale === 'ar') {
            $subject = 'تذكير: جلسة الإرشاد تبدأ بعد 60 دقيقة';
            $bodyLines = [
                "مرحبًا {$mentorName}،",
                "تذكير بأن جلسة الإرشاد {$sessionTitle} مع المشارك {$participantName} ستبدأ بعد 60 دقيقة.",
                '',
                "التاريخ: {$sessionDate}",
                '',
                "الوقت: {$sessionTime} ({$timezone})",
                '',
                "المدة: {$duration}",
                '',
                "رابط الانضمام: {$joinLink}.",
                '',
                'في حال واجهت أي مشكلة، تواصل معنا عبر قسم المساعدة في المنصة.',
            ];
        } else {
            $subject = 'Reminder: Your mentorship session starts in 60 minutes';
            $bodyLines = [
                "Hi {$mentorName},",
                "This is a reminder that your mentorship session {$sessionTitle} with {$participantName} starts in 60 minutes.",
                '',
                "Date: {$sessionDate}",
                '',
                "Time: {$sessionTime} ({$timezone})",
                '',
                "Duration: {$duration}",
                '',
                "Join link: {$joinLink}.",
                '',
                'Need help? Contact us at the support section on the platform.',
            ];
        }

        $mail->subject($subject);

        foreach ($bodyLines as $line) {
            $mail->line($line);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $scheduledAt = $this->session->scheduled_at;
        $sessionDate = $scheduledAt ? $scheduledAt->format('Y-m-d') : null;
        $sessionTime = $scheduledAt ? $scheduledAt->format('H:i') : null;
        $timezone   = config('app.timezone', 'UTC');
        $duration   = $this->session->duration_formatted ?? ($this->session->duration_minutes . ' min');
        $joinLink   = $this->session->join_url ?: config('app.frontend_url');
        $sessionTitle = $this->session->title ?? '';

        $participantEn = null;
        $participantAr = null;
        if ($this->session->participant) {
            $name = $this->session->participant->name ?? null;
            if (is_array($name)) {
                $participantEn = $name['en'] ?? ($name['ar'] ?? '');
                $participantAr = $name['ar'] ?? ($name['en'] ?? '');
            } else {
                $participantEn = $participantAr = $name;
            }
        }

        $mentorName = $notifiable->name ?? '';

        $bodyAr = "تذكير: جلستك {$sessionTitle} مع {$participantAr} تبدأ بعد 60 دقيقة اليوم {$sessionDate} الساعة {$sessionTime} ({$timezone}) - اضغط للانضمام: {$joinLink}.";
        $bodyEn = "Reminder: Your session {$sessionTitle} with {$participantEn} starts in 60 minutes on {$sessionDate} at {$sessionTime} ({$timezone}) - tap to join: {$joinLink}.";

        return [
            'type' => 'session_reminder_60_minutes',
            'session_id' => $this->session->id,
            'title' => [
                'ar' => 'تذكير: جلستك تبدأ بعد 60 دقيقة',
                'en' => 'Reminder: Your mentorship session starts in 60 minutes',
            ],
            'body' => [
                'ar' => $bodyAr,
                'en' => $bodyEn,
            ],
            'mentor_name' => $mentorName,
            'participant_name_en' => $participantEn,
            'participant_name_ar' => $participantAr,
            'scheduled_at' => $scheduledAt ? $scheduledAt->format('Y-m-d H:i:s') : null,
            'session_date' => $sessionDate,
            'session_time' => $sessionTime,
            'timezone' => $timezone,
            'duration' => $duration,
            'join_url' => $joinLink,
            'video_tool' => $this->session->video_tool,
        ];
    }
}


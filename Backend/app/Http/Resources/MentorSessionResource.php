<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MentorSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Get current locale for date formatting
        $locale = app()->getLocale() ?? 'en';

        // Helper function to format date with locale
        $formatDateWithLocale = function ($date) use ($locale) {
            if (!$date) {
                return null;
            }
            // Laravel automatically converts scheduled_at from UTC to app timezone (Asia/Riyadh) when reading from DB
            // So date is already in Asia/Riyadh timezone
            $carbonDate = $date->copy()->locale($locale);
            $datePart = $carbonDate->translatedFormat('M d, Y');
            $timePart = $carbonDate->translatedFormat('g:i A');
            // Use localized separator: "في" for Arabic, "at" for English
            $separator = $locale === 'ar' ? ' في ' : ' at ';
            return $datePart . $separator . $timePart;
        };

        // Laravel automatically converts scheduled_at from UTC to app timezone (Asia/Riyadh) when reading from DB
        // So scheduled_at is already in Asia/Riyadh timezone
        $scheduledAtLocal = $this->scheduled_at ? $this->scheduled_at->copy() : null;
        $endTimeLocal = $this->end_time ? $this->end_time->copy() : null;
        $proposedTimeLocal = $this->proposed_time ? $this->proposed_time->copy() : null;

        return [
            'id' => $this->id,
            'title' => $this->title ?: __('sessions.topic_required'),
            'description' => $this->description,
            'scheduled_at' => $scheduledAtLocal?->format('Y-m-d H:i:s'),
            'scheduled_at_formatted' => $formatDateWithLocale($this->scheduled_at),
            'duration_minutes' => $this->duration_minutes,
            'duration_formatted' => $this->duration_formatted,
            'end_time' => $endTimeLocal?->format('Y-m-d H:i:s'),
            'end_time_formatted' => $formatDateWithLocale($this->end_time),
            'status' => $this->status,
            'status_display_name' => $this->status_display_name,
            'video_tool' => $this->video_tool,
            'video_tool_display_name' => $this->video_tool_display_name,
            'meeting_id' => $this->meeting_id,
            'join_url' => $this->join_url,
            'password' => $this->password,
            'calendar_event_id' => $this->calendar_event_id,
            //'notes' => $this->notes,
            'declined_reason' => $this->declined_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'proposed_time' => $proposedTimeLocal?->format('Y-m-d H:i:s'),
            'proposed_time_formatted' => $formatDateWithLocale($this->proposed_time),
            'has_proposed_time' => $this->hasProposedTime(),
            'is_pending_request' => $this->isPendingRequest(),
            'feedback' => $this->feedback,
            'feedback_comments' => $this->feedback_comments,
            'feedback_strengths' => $this->feedback_strengths,
            'feedback_improvements' => $this->feedback_improvements,
            'rating' => $this->rating,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'ended_at' => $this->ended_at?->format('Y-m-d H:i:s'),
            'is_upcoming' => $this->isUpcoming(),
            'is_in_progress' => $this->isInProgress(),
            'is_completed' => $this->isCompleted(),
            'is_cancelled' => $this->isCancelled(),
            'mentor' => $this->when($this->mentor, function () {
                // Get translated mentor name based on request locale
                $lang = request()->getPreferredLanguage(['en', 'ar']);
                $mentorName = is_array($this->mentor->name)
                    ? ($this->mentor->name[$lang] ?? $this->mentor->name['en'] ?? $this->mentor->name['ar'] ?? __('sessions.mentor_not_found_in_session'))
                    : ($this->mentor->name ?? __('sessions.mentor_not_found_in_session'));

                return [
                    'id' => $this->mentor->id ?? null,
                    'name' => $mentorName,
                    'email' => $this->mentor->email ?? null,
                    'profession' => $this->mentor->profession ?: null,
                    'experience' => $this->mentor->experience ?: null,
                    'brief' => $this->mentor->brief ?:null,
                    'image' => !empty($this->mentor->image) ? Storage::url($this->mentor->image) : null,
                ];
            }, function () {
                // Return null mentor with error message
                return [
                    'id' => null,
                    'name' => __('sessions.mentor_not_found_in_session'),
                    'email' => null,
                ];
            }),
            'participant' => $this->when($this->participant, function () {
                // Get translated participant name based on request locale
                $lang = request()->getPreferredLanguage(['en', 'ar']);
                $participantName = is_array($this->participant->name)
                    ? ($this->participant->name[$lang] ?? $this->participant->name['en'] ?? $this->participant->name['ar'] ?? 'N/A')
                    : ($this->participant->name ?? 'N/A');

                return [
                    'id' => $this->participant->id ?? null,
                    'name' => $participantName,
                    'email' => $this->participant->email ?? null,
                    'current_role' => $this->participant->current_role ? __('participant.' . $this->participant->current_role) : null,
                ];
            }, null),
            'program' => $this->when($this->program, function () {
                // Get translated program title based on request locale
                $lang = request()->getPreferredLanguage(['en', 'ar']);
                $programTitle = is_array($this->program->title)
                    ? ($this->program->title[$lang] ?? $this->program->title['en'] ?? $this->program->title['ar'] ?? 'N/A')
                    : ($this->program->title ?? 'N/A');

                return [
                    'id' => $this->program->id ?? null,
                    'title' => $programTitle,
                ];
            }, null),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

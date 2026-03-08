<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;

class MentorSession extends Model
{
    use HasFactory, LogsActivity, HasActivityLog;

    protected $fillable = [
        'mentor_id',
        'participant_id',
        'program_id',
        'title',
        'description',
        'scheduled_at',
        'duration_minutes',
        'status',
        'video_tool',
        'meeting_id',
        'join_url',
        'password',
        'calendar_event_id',
        'notes',
        'declined_reason',
        'cancellation_reason',
        'proposed_time',
        'feedback',
        'feedback_comments',
        'feedback_strengths',
        'feedback_improvements',
        'rating',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'proposed_time' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'calendar_event_id' => 'array',
        'duration_minutes' => 'integer',
        'rating' => 'integer',
    ];

    protected array $logFields = [
        'title',
        'scheduled_at',
        'duration_minutes',
        'status',
        'video_tool',
        'meeting_id',
        'join_url',
        'notes',
        'feedback',
        'feedback_comments',
        'feedback_strengths',
        'feedback_improvements',
        'rating',
    ];

    protected string $moduleName = 'Mentor Session';
    protected string $logName = 'mentor_session';

    const STATUSES = [
        'scheduled' => 'Scheduled',
        'confirmed' => 'Confirmed',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No Show',
    ];

    const VIDEO_TOOLS = [
        'zoom' => 'Zoom',
//        'teams' => 'Microsoft Teams',
        'google_meet' => 'Google Meet',
    ];

    /**
     * Get the mentor for this session.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    /**
     * Get the participant for this session.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get the program for this session.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the video tool integration for this session.
     */
    public function videoTool(): BelongsTo
    {
        return $this->belongsTo(MentorVideoTool::class, 'mentor_id', 'mentor_id')
            ->where('tool_type', $this->video_tool);
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        if (!$this->status) {
            return 'N/A';
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Get the video tool display name.
     */
    public function getVideoToolDisplayNameAttribute(): string
    {
        if (!$this->video_tool) {
            return 'N/A';
        }

        return self::VIDEO_TOOLS[$this->video_tool] ?? $this->video_tool;
    }

    /**
     * Check if the session is upcoming.
     * A session is upcoming if:
     * 1. It has status 'scheduled' or 'confirmed' (not cancelled)
     * 2. The session end time (scheduled_at + duration_minutes) is in the future
     */
    public function isUpcoming(): bool
    {
        // Must be in scheduled or confirmed status
        if (!in_array($this->status, ['scheduled', 'confirmed'])) {
            return false;
        }

        // Must have a scheduled_at date
        if (!$this->scheduled_at) {
            return false;
        }

        // Check if session end time is in the future
        $endTime = $this->scheduled_at->copy()->addMinutes($this->duration_minutes ?? 30);
        return $endTime->isFuture();
    }

    /**
     * Check if the session is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the session is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the session duration in a readable format.
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        // Get current locale for translations
        $locale = app()->getLocale();
        $hourUnit = __('notifications.hour', [], $locale);
        $minuteUnit = __('notifications.minute', [], $locale);

        if ($hours > 0) {
            if ($minutes > 0) {
                return "{$hours}{$hourUnit} {$minutes}{$minuteUnit}";
            }
            return "{$hours}{$hourUnit}";
        }

        return "{$minutes}{$minuteUnit}";
    }

    /**
     * Get the session end time.
     */
    public function getEndTimeAttribute(): ?\Carbon\Carbon
    {
        if (!$this->scheduled_at) {
            return null;
        }

        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes ?? 0);
    }

    /**
     * Scope to get sessions by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get upcoming sessions.
     * A session is upcoming if:
     * 1. It has status 'scheduled' or 'confirmed' (not cancelled)
     * 2. The session end time (scheduled_at + duration_minutes) is in the future
     */
    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNotNull('scheduled_at')
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 30) MINUTE) > NOW()');
    }

    /**
     * Scope to get past sessions.
     * A session is past if:
     * 1. The session end time (scheduled_at + duration_minutes) has passed
     * 2. The status is not 'cancelled' (cancelled sessions have their own category)
     */
    public function scopePast($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL COALESCE(duration_minutes, 30) MINUTE) < NOW()')
            ->where('status', '!=', 'cancelled');
    }

    /**
     * Scope to get canceled sessions.
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get sessions for a specific mentor.
     */
    public function scopeForMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    /**
     * Scope to get sessions for a specific participant.
     */
    public function scopeForParticipant($query, int $participantId)
    {
        return $query->where('participant_id', $participantId);
    }

    /**
     * Scope to get sessions for a specific program.
     */
    public function scopeForProgram($query, int $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope to get sessions by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get pending session requests (awaiting mentor response).
     */
    public function scopePendingRequests($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Check if the session is a pending request.
     */
    public function isPendingRequest(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the session has a proposed time.
     */
    public function hasProposedTime(): bool
    {
        return $this->proposed_time !== null;
    }
}

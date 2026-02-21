<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TaskAssignment extends Model
{
    use HasFactory, HasTranslations, LogsActivity;

    protected $fillable = [
        'task_template_id',
        'program_id',
        'stage_id',
        'assignment_type',
        'team_id',
        'participant_id',
        'title',
        'description',
        'instructions',
        'due_date',
        'status',
        'allowed_file_formats',
        'max_file_size_mb',
        'assignment_notes',
        'assigned_by',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'is_archived',
    ];

    public $translatable = ['title', 'description', 'instructions', 'assignment_notes'];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'instructions' => 'array',
        'assignment_notes' => 'array',
        'allowed_file_formats' => 'array',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'max_file_size_mb' => 'integer',
        'is_archived' => 'boolean',
    ];

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVISION_REQUESTED = 'revision_requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_SUBMITTED,
        self::STATUS_REVISION_REQUESTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const STATUS_COLORS = [
        self::STATUS_NOT_STARTED => 'gray',
        self::STATUS_IN_PROGRESS => 'info',
        self::STATUS_SUBMITTED => 'warning',
        self::STATUS_REVISION_REQUESTED => 'danger',
        self::STATUS_APPROVED => 'success',
        self::STATUS_REJECTED => 'danger',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(TaskTemplate::class, 'task_template_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class)->orderBy('version', 'desc');
    }

    public function latestSubmission()
    {
        return $this->hasOne(TaskSubmission::class)->latestOfMany('version');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where(function ($q) use ($teamId) {
            $q->where('team_id', $teamId)
              ->orWhere('assignment_type', 'all');
        });
    }

    public function scopeForParticipant($query, int $participantId)
    {
        return $query->where(function ($q) use ($participantId) {
            $q->where('participant_id', $participantId)
              ->orWhere('assignment_type', 'all');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }

    // Status helpers
    public function isOverdue(): bool
    {
        return $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }

    public function canSubmit(): bool
    {
        return in_array($this->status, [
            self::STATUS_NOT_STARTED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_REVISION_REQUESTED,
        ]);
    }

    public function canReview(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function markAsSubmitted(): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function approve(int $reviewerId, ?string $feedback = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
        ]);
    }

    public function reject(int $reviewerId, ?string $feedback = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
        ]);
    }

    public function requestRevision(int $reviewerId, ?string $feedback = null): void
    {
        $this->update([
            'status' => self::STATUS_REVISION_REQUESTED,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
        ]);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getAssigneeNameAttribute(): string
    {
        if ($this->assignment_type === 'team' && $this->team) {
            return $this->team->name;
        }
        if ($this->assignment_type === 'participant' && $this->participant) {
            return $this->participant->name;
        }
        return __('All Participants');
    }
}

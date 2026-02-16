<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_assignment_id',
        'submitted_by',
        'form_submissions',
        'files',
        'notes',
        'version',
        'status',
        'admin_feedback',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected $casts = [
        'form_submissions' => 'array',
        'files' => 'array',
        'version' => 'integer',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // Relationships
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class, 'task_assignment_id');
    }

    public function submittedByParticipant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'submitted_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Helpers
    public function isLatest(): bool
    {
        return $this->id === $this->assignment->latestSubmission?->id;
    }
}

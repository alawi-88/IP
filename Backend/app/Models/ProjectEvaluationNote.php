<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEvaluationNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admin_id',
        'project_evaluation_id',
        'content',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const TYPES = [
        'general_feedback' => 'General Feedback',
        'issue_detected' => 'Issue Detected',
        'administrative_decision' => 'Administrative Decision',
        'other' => 'Other',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ProjectEvaluation::class, 'project_evaluation_id');
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function canDelete(): bool
    {
        return $this->admin_id === auth()->id();
    }
}

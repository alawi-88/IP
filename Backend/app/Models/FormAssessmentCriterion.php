<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasActivityLog;

class FormAssessmentCriterion extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'form_id',
        'name',
        'description',
        'instruction',
        'weight',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'integer',
        'sort_order' => 'integer',
    ];

    protected array $logFields = [
        'form_id',
        'name',
        'description',
        'instruction',
        'weight',
        'status',
        'sort_order',
    ];

    protected string $moduleName = 'Form Assessment Criterion';
    protected string $logName = 'form_assessment_criterion';

    /**
     * Get the form that owns this criterion.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get the AI scoring config for this form.
     */
    public function aiScoringConfig(): BelongsTo
    {
        return $this->belongsTo(FormAiScoringConfig::class, 'form_id', 'form_id');
    }

    /**
     * Check if criterion is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope to get only active criteria.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only disabled criteria.
     */
    public function scopeDisabled($query)
    {
        return $query->where('status', 'disabled');
    }

    /**
     * Get the form fields that this criterion assesses.
     */
    public function formFields(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            FormField::class,
            'form_assessment_criterion_form_field',
            'form_assessment_criterion_id',
            'form_field_id'
        )->withTimestamps();
    }

    /**
     * Get the contextual form fields that provide additional context for this criterion.
     */
    public function contextualFields(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            FormField::class,
            'form_assessment_criterion_context_field',
            'form_assessment_criterion_id',
            'form_field_id'
        )->withTimestamps();
    }
}


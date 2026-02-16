<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RegistrationEvaluator extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RegistrationEvaluation::class);
    }

    public function assignedSections(): HasMany
    {
        return $this->hasMany(RegistrationEvaluatorSection::class);
    }

    public function assignedForms(): BelongsToMany
    {
        return $this->belongsToMany(
            RegistrationEvaluationForm::class,
            'registration_evaluator_sections',
            'registration_evaluator_id',
            'registration_evaluation_form_id'
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getEvaluationScoreForApplication(int $applicationId): float
    {
        return $this->evaluations()
            ->where('competition_application_id', $applicationId)
            ->sum('score');
    }

    public function hasCompletedEvaluation(int $applicationId): bool
    {
        $totalCriteria = RegistrationEvaluationCriterion::whereIn(
            'registration_evaluation_form_id',
            $this->assignedForms()->pluck('registration_evaluation_forms.id')
        )->count();

        $scoredCriteria = $this->evaluations()
            ->where('competition_application_id', $applicationId)
            ->count();

        return $totalCriteria > 0 && $scoredCriteria >= $totalCriteria;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_application_id',
        'registration_evaluator_id',
        'registration_evaluation_form_id',
        'registration_evaluation_criterion_id',
        'score',
        'comment',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    // Relationships
    public function application(): BelongsTo
    {
        return $this->belongsTo(ProgramApplication::class, 'program_application_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(RegistrationEvaluator::class, 'registration_evaluator_id');
    }

    public function evaluationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationEvaluationForm::class, 'registration_evaluation_form_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RegistrationEvaluationCriterion::class, 'registration_evaluation_criterion_id');
    }
}

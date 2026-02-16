<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationEvaluatorSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_evaluator_id',
        'registration_evaluation_form_id',
    ];

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(RegistrationEvaluator::class, 'registration_evaluator_id');
    }

    public function evaluationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationEvaluationForm::class, 'registration_evaluation_form_id');
    }
}

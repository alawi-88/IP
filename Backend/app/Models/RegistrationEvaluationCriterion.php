<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class RegistrationEvaluationCriterion extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'registration_evaluation_criteria';

    protected $fillable = [
        'registration_evaluation_form_id',
        'name',
        'description',
        'max_score',
        'weight',
        'sort_order',
    ];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'max_score' => 'integer',
        'weight' => 'integer',
        'sort_order' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(RegistrationEvaluationForm::class, 'registration_evaluation_form_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RegistrationEvaluation::class, 'registration_evaluation_criterion_id');
    }
}

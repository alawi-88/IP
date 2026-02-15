<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class AssessmentCriterion extends Model
{
    protected $fillable = [
        'registration_form_config_id',
        'description',
        'max_score',
        'sort_order',
    ];

    protected $casts = [
        'max_score' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the registration form config that owns this criterion.
     */
    public function registrationFormConfig(): BelongsTo
    {
        return $this->belongsTo(RegistrationFormConfig::class);
    }

}


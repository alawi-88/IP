<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class RegistrationEvaluationForm extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'program_id',
        'name',
        'description',
        'dimension',
        'scoring_scale',
        'status',
        'sort_order',
    ];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'sort_order' => 'integer',
    ];

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Relationships
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RegistrationEvaluationCriterion::class)->orderBy('sort_order');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RegistrationEvaluation::class);
    }

    public function evaluatorSections(): HasMany
    {
        return $this->hasMany(RegistrationEvaluatorSection::class);
    }

    // Helpers
    public function getMaxPossibleScore(): int
    {
        return $this->criteria()->sum('max_score');
    }

    public function publish(): void
    {
        $this->update(['status' => 'published']);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }
}

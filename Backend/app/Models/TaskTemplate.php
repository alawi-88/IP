<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TaskTemplate extends Model
{
    use HasFactory, HasTranslations, LogsActivity;

    protected $fillable = [
        'program_id',
        'form_id',
        'title',
        'description',
        'instructions',
        'difficulty_level',
        'estimated_hours',
        'category',
        'version',
        'created_by',
        'is_archived',
    ];

    public $translatable = ['title', 'description', 'instructions'];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'instructions' => 'array',
        'estimated_hours' => 'integer',
        'version' => 'integer',
        'is_archived' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    // Relationships
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeForProgram($query, int $programId)
    {
        return $query->where('program_id', $programId);
    }
}

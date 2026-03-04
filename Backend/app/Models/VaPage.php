<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VaPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'va_section_id',
        'page_key',
        'title_en',
        'title_ar',
        'content',
        'completion_percentage',
        'status',
        'order',
        'last_edited_at',
        'auto_saved_at',
    ];

    protected $casts = [
        'content' => 'json',
        'completion_percentage' => 'decimal:2',
        'last_edited_at' => 'datetime',
        'auto_saved_at' => 'datetime',
    ];

    /**
     * Get the VA section that owns this page
     */
    public function vaSection(): BelongsTo
    {
        return $this->belongsTo(VaSection::class);
    }

    /**
     * Get all AI generations for this page
     */
    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class)->orderBy('created_at', 'desc');
    }

    /**
     * Mark page as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->completion_percentage = 100;
        $this->save();
        
        // Update section completion
        $this->vaSection->calculateCompletion();
    }

    /**
     * Update page content and completion
     */
    public function updateContent(array $content, ?float $completion = null): void
    {
        $this->content = $content;
        $this->last_edited_at = now();
        $this->auto_saved_at = now();
        
        if ($completion !== null) {
            $this->completion_percentage = round($completion, 2);
            if ($completion === 100) {
                $this->status = 'completed';
            } elseif ($completion > 0 && $this->status === 'draft') {
                $this->status = 'in_progress';
            }
        }
        
        $this->save();
        
        // Update section completion
        $this->vaSection->calculateCompletion();
    }
}

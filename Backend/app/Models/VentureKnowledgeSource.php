<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentureKnowledgeSource extends Model
{
    protected $fillable = [
        'title',
        'content',
        'type',
        'file_path',
        'is_active',
        'priority',
        'max_tokens',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'max_tokens' => 'integer',
    ];

    /**
     * Get the admin user who created this knowledge source.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to only active knowledge sources.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by priority (highest first).
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority');
    }

    /**
     * Get all active knowledge sources as formatted text for prompt injection.
     * Each source is truncated to its max_tokens limit.
     */
    public static function getKnowledgeForPrompt(): string
    {
        $sources = static::active()->ordered()->get();

        if ($sources->isEmpty()) {
            return '';
        }

        $knowledgeText = '';
        foreach ($sources as $source) {
            $content = $source->content;
            if (strlen($content) > $source->max_tokens) {
                $content = substr($content, 0, $source->max_tokens) . '...';
            }
            $knowledgeText .= "### {$source->title}\n{$content}\n\n";
        }

        return $knowledgeText;
    }
}

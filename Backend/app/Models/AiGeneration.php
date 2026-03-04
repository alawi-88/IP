<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'va_page_id',
        'user_id',
        'field_key',
        'prompt',
        'response',
        'status',
        'model_used',
        'tokens_used',
        'generation_time_ms',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'generation_time_ms' => 'integer',
    ];

    /**
     * Get the VA page that owns this generation
     */
    public function vaPage(): BelongsTo
    {
        return $this->belongsTo(VaPage::class);
    }

    /**
     * Get the user that triggered this generation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark generation as accepted
     */
    public function markAsAccepted(): void
    {
        $this->status = 'accepted';
        $this->save();
    }

    /**
     * Mark generation as modified
     */
    public function markAsModified(): void
    {
        $this->status = 'modified';
        $this->save();
    }

    /**
     * Mark generation as dismissed
     */
    public function markAsDismissed(): void
    {
        $this->status = 'dismissed';
        $this->save();
    }
}

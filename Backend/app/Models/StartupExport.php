<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StartupExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'startup_id',
        'user_id',
        'format',
        'file_path',
        'status',
    ];

    protected $casts = [
        'format' => 'string',
        'status' => 'string',
    ];

    /**
     * Get the startup that owns this export
     */
    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class);
    }

    /**
     * Get the user that created this export
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark export as processing
     */
    public function markAsProcessing(): void
    {
        $this->status = 'processing';
        $this->save();
    }

    /**
     * Mark export as completed
     */
    public function markAsCompleted(string $filePath): void
    {
        $this->status = 'completed';
        $this->file_path = $filePath;
        $this->save();
    }

    /**
     * Mark export as failed
     */
    public function markAsFailed(): void
    {
        $this->status = 'failed';
        $this->save();
    }
}

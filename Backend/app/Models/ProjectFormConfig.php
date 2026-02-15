<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ProjectFormConfig extends Model
{
    use LogsActivity, HasActivityLog;


    protected $fillable = [
        'form_id',
        'allow_track_change',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'allow_track_change' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected array $logFields = [
        'from_id',
        'form.name',
        'allow_track_change',
        'form.name',
        'is_archived',
        'archived_at',
    ];

    protected string $moduleName = 'Project Form Config';
    protected string $logName = 'project_form_config';

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Check if the configuration is archived
     */
    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    /**
     * Archive the configuration
     */
    public function archive(): bool
    {
        return $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    /**
     * Restore the configuration
     */
    public function restore(): bool
    {
        return $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    /**
     * Scope to get only active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope to get only archived configurations
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}

<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class TeamFormConfig extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'competition_id',
        'is_active',
        'min_team_members',
        'max_team_members',
        'allow_track_selection',
        'require_same_track',
        'auto_publish_teams',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_track_selection' => 'boolean',
        'require_same_track' => 'boolean',
        'auto_publish_teams' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected array $logFields = [
        'is_active',
        'min_team_members',
        'max_team_members',
        'allow_track_selection',
        'require_same_track',
        'auto_publish_teams',
        'is_archived',
        'archived_at',
        'competition.title',
        'competition_id',
    ];

    protected string $moduleName = 'Team Form Config';
    protected string $logName = 'team_form_config';

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function archive(): bool
    {
        return $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    public function restore(): bool
    {
        return $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }
}



<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApprovalWorkflow extends Model
{
    use LogsActivity;

    protected $fillable = [
        'action',
        'levels',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['action', 'levels', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('approval_workflow')
            ->setDescriptionForEvent(fn(string $eventName) => "ApprovalWorkflow was {$eventName}");
    }

    public function approvalLevels(): HasMany
    {
        return $this->hasMany(ApprovalLevel::class);
    }

    /**
     * Get roles for a specific level
     */
    public function getRolesForLevel(int $levelNumber): array
    {
        $level = $this->approvalLevels()->where('level_number', $levelNumber)->first();
        return $level ? $level->role_ids : [];
    }

    /**
     * Get all roles assigned to this workflow
     */
    public function getAllRoles(): array
    {
        $allRoles = [];
        foreach ($this->approvalLevels as $level) {
            $allRoles = array_merge($allRoles, $level->role_ids);
        }
        return array_unique($allRoles);
    }

    /**
     * Check if a role is assigned to any level of this workflow
     */
    public function hasRole(int $roleId): bool
    {
        return in_array($roleId, $this->getAllRoles());
    }

    /**
     * Get the maximum level number for this workflow
     */
    public function getMaxLevel(): int
    {
        return $this->approvalLevels()->max('level_number') ?? 0;
    }

    /**
     * Scope for active workflows
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive workflows
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}

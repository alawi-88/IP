<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;

class ApprovalLevel extends Model
{
    use LogsActivity;

    protected $fillable = [
        'approval_workflow_id',
        'level_number',
        'role_ids',
        'required_approvals',
    ];

    protected $casts = [
        'role_ids' => 'array',
    ];

    /**
     * Set the level number attribute
     */
    public function setLevelNumberAttribute($value)
    {
        // Ensure level number is never 0 or negative
        $this->attributes['level_number'] = max(1, (int)$value);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['approval_workflow_id', 'level_number', 'role_ids', 'required_approvals'])
            ->logOnlyDirty()
            ->useLogName('approval_level')
            ->setDescriptionForEvent(fn(string $eventName) => "ApprovalLevel was {$eventName}");
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }

    /**
     * Get the roles for this level
     */
    public function roles()
    {
        $roleIds = $this->role_ids ?? [];
        if (empty($roleIds)) {
            return collect();
        }
        return Role::whereIn('id', $roleIds)->get();
    }

    /**
     * Get role names for this level
     */
    public function getRoleNames(): array
    {
        // Ensure role_ids is an array
        $roleIds = is_array($this->role_ids) ? $this->role_ids : json_decode($this->role_ids, true);
        
        if (empty($roleIds) || !is_array($roleIds)) {
            return [];
        }
        
        $roles = Role::whereIn('id', $roleIds)->get();
        return $roles->pluck('name')->toArray();
    }

    /**
     * Check if a specific role is assigned to this level
     */
    public function hasRole(int $roleId): bool
    {
        return in_array($roleId, $this->role_ids);
    }

    /**
     * Get the number of roles assigned to this level
     */
    public function getRoleCount(): int
    {
        return count($this->role_ids);
    }

    /**
     * Check if this level has enough roles for required approvals
     */
    public function hasEnoughRoles(): bool
    {
        return $this->getRoleCount() >= $this->required_approvals;
    }
}

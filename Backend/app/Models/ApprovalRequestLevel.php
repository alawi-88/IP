<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApprovalRequestLevel extends Model
{
    use LogsActivity;

    protected $fillable = [
        'approval_request_id',
        'level_number',
        'status',
        'approver_id',
        'approver_comment',
        'approved_at',
        'rejected_at',
        'role_ids',
        'required_approvals',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'role_ids' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['approval_request_id', 'level_number', 'status', 'approver_id', 'approver_comment'])
            ->logOnlyDirty()
            ->useLogName('approval_request_level')
            ->setDescriptionForEvent(fn(string $eventName) => "ApprovalRequestLevel was {$eventName}");
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ApprovalRequestLevelVote::class, 'approval_request_level_id');
    }

    public function approvalsCount(): int
    {
        return (int) $this->votes()->where('decision', 'approved')->count();
    }

    public function rejectionsCount(): int
    {
        return (int) $this->votes()->where('decision', 'rejected')->count();
    }

    public function requiredApprovals(): int
    {
        return (int) ($this->required_approvals ?? 1);
    }

    public function hasVoted(User $user): bool
    {
        return $this->votes()->where('user_id', $user->id)->exists();
    }

    public function getNextRoleToVote(): ?int
    {
        $roleIds = $this->role_ids ?? [];
        if (empty($roleIds) || !is_array($roleIds)) {
            return null;
        }

        $votes = $this->votes()->with('user.roles')->orderBy('created_at')->get();
        
        $votedRoleIds = [];
        foreach ($votes as $vote) {
            $userRoleIds = $vote->user->roles->pluck('id')->toArray();
            foreach ($roleIds as $roleId) {
                if (in_array($roleId, $userRoleIds) && !in_array($roleId, $votedRoleIds)) {
                    $votedRoleIds[] = $roleId;
                    break;
                }
            }
        }

        foreach ($roleIds as $roleId) {
            if (!in_array($roleId, $votedRoleIds)) {
                return $roleId;
            }
        }

        return null;
    }

    public function hasRoleVoted(int $roleId): bool
    {
        $roleIds = $this->role_ids ?? [];
        if (empty($roleIds) || !is_array($roleIds) || !in_array($roleId, $roleIds)) {
            return false;
        }

        $votes = $this->votes()->with('user.roles')->orderBy('created_at')->get();
        
        $votedRoleIds = [];
        foreach ($votes as $vote) {
            $userRoleIds = $vote->user->roles->pluck('id')->toArray();
            
            foreach ($roleIds as $rId) {
                if (in_array($rId, $userRoleIds) && !in_array($rId, $votedRoleIds)) {
                    $votedRoleIds[] = $rId;
                    break;
                }
            }
        }

        return in_array($roleId, $votedRoleIds);
    }

    public function canUserVoteNow(User $user): bool
    {
        $userRoleIds = $user->roles->pluck('id')->toArray();
        $levelRoleIds = $this->role_ids ?? [];
        
        if (empty($levelRoleIds) || !is_array($levelRoleIds)) {
            return false;
        }

        $matchingRoleIds = array_intersect($userRoleIds, $levelRoleIds);
        if (empty($matchingRoleIds)) {
            return false;
        }

        foreach ($matchingRoleIds as $roleId) {
            if (!$this->hasRoleVoted($roleId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this level is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if this level is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if this level is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Record an approval vote. Finalizes the level only when required approvals are reached.
     *
     * @return array{success: bool, finalized: bool, approvals: int, rejections: int, required: int, status: string, message?: string}
     */
    public function approve(User $approver, string $comment = null): array
    {
        if (!$this->isPending()) {
            return [
                'success' => false,
                'finalized' => false,
                'approvals' => $this->approvalsCount(),
                'rejections' => $this->rejectionsCount(),
                'required' => $this->requiredApprovals(),
                'status' => $this->status,
                'message' => 'Level is not pending.',
            ];
        }

        if ($this->hasVoted($approver)) {
            return [
                'success' => false,
                'finalized' => false,
                'approvals' => $this->approvalsCount(),
                'rejections' => $this->rejectionsCount(),
                'required' => $this->requiredApprovals(),
                'status' => $this->status,
                'message' => 'User already voted on this level.',
            ];
        }

        if (!$this->canUserVoteNow($approver)) {
            $userRoleIds = $approver->roles->pluck('id')->toArray();
            $levelRoleIds = $this->role_ids ?? [];
            $matchingRoleIds = array_intersect($userRoleIds, $levelRoleIds);
            
            if (empty($matchingRoleIds)) {
                return [
                    'success' => false,
                    'finalized' => false,
                    'approvals' => $this->approvalsCount(),
                    'rejections' => $this->rejectionsCount(),
                    'required' => $this->requiredApprovals(),
                    'status' => $this->status,
                    'message' => 'You do not have a role that can vote on this level.',
                ];
            }
            
            return [
                'success' => false,
                'finalized' => false,
                'approvals' => $this->approvalsCount(),
                'rejections' => $this->rejectionsCount(),
                'required' => $this->requiredApprovals(),
                'status' => $this->status,
                'message' => 'All roles matching your permissions have already voted on this level.',
            ];
        }

        $userRoleIds = $approver->roles->pluck('id')->toArray();
        $levelRoleIds = $this->role_ids ?? [];
        $matchingRoleIds = array_intersect($userRoleIds, $levelRoleIds);
        
        if (!empty($matchingRoleIds)) {
            $matchingRoleId = reset($matchingRoleIds);
            if ($this->hasRoleVoted($matchingRoleId)) {
                $role = \Spatie\Permission\Models\Role::find($matchingRoleId);
                $roleName = $role ? $role->name : 'Unknown';
                return [
                    'success' => false,
                    'finalized' => false,
                    'approvals' => $this->approvalsCount(),
                    'rejections' => $this->rejectionsCount(),
                    'required' => $this->requiredApprovals(),
                    'status' => $this->status,
                    'message' => "A user with role '{$roleName}' has already voted on this level.",
                ];
            }
        }

        $this->votes()->create([
            'user_id' => $approver->id,
            'decision' => 'approved',
            'comment' => $comment,
        ]);

        $approvals = $this->approvalsCount();
        $rejections = $this->rejectionsCount();
        $required = $this->requiredApprovals();

        if ($approvals >= $required) {
            $this->update([
                'status' => 'approved',
                // Keep legacy fields for existing UI; store the last approver/comment.
                'approver_id' => $approver->id,
                'approver_comment' => $comment,
                'approved_at' => now(),
            ]);

            // Check if the entire request should be approved
            if ($this->approvalRequest->isFullyApproved()) {
                $this->approvalRequest->approve();
            }

            return [
                'success' => true,
                'finalized' => true,
                'approvals' => $approvals,
                'rejections' => $rejections,
                'required' => $required,
                'status' => 'approved',
            ];
        }

        return [
            'success' => true,
            'finalized' => false,
            'approvals' => $approvals,
            'rejections' => $rejections,
            'required' => $required,
            'status' => 'pending',
        ];
    }

    /**
     * Record a rejection vote. Finalizes the level only when required rejections are reached.
     *
     * @return array{success: bool, finalized: bool, approvals: int, rejections: int, required: int, status: string, message?: string}
     */
    public function reject(User $approver, string $comment = null): array
    {
        if (!$this->isPending()) {
            return [
                'success' => false,
                'finalized' => false,
                'approvals' => $this->approvalsCount(),
                'rejections' => $this->rejectionsCount(),
                'required' => $this->requiredApprovals(),
                'status' => $this->status,
                'message' => 'Level is not pending.',
            ];
        }

        if ($this->hasVoted($approver)) {
            return [
                'success' => false,
                'finalized' => false,
                'approvals' => $this->approvalsCount(),
                'rejections' => $this->rejectionsCount(),
                'required' => $this->requiredApprovals(),
                'status' => $this->status,
                'message' => 'User already voted on this level.',
            ];
        }

        if (!$this->canUserVoteNow($approver)) {
            $userRoleIds = $approver->roles->pluck('id')->toArray();
            $levelRoleIds = $this->role_ids ?? [];
            $matchingRoleIds = array_intersect($userRoleIds, $levelRoleIds);
            
            if (empty($matchingRoleIds)) {
                return [
                    'success' => false,
                    'finalized' => false,
                    'approvals' => $this->approvalsCount(),
                    'rejections' => $this->rejectionsCount(),
                    'required' => $this->requiredApprovals(),
                    'status' => $this->status,
                    'message' => 'You do not have a role that can vote on this level.',
                ];
            }
            
            return [
                'success' => false,
                'finalized' => false,
                'approvals' => $this->approvalsCount(),
                'rejections' => $this->rejectionsCount(),
                'required' => $this->requiredApprovals(),
                'status' => $this->status,
                'message' => 'All roles matching your permissions have already voted on this level.',
            ];
        }

        $userRoleIds = $approver->roles->pluck('id')->toArray();
        $levelRoleIds = $this->role_ids ?? [];
        $matchingRoleIds = array_intersect($userRoleIds, $levelRoleIds);
        
        if (!empty($matchingRoleIds)) {
            $matchingRoleId = reset($matchingRoleIds);
            if ($this->hasRoleVoted($matchingRoleId)) {
                $role = \Spatie\Permission\Models\Role::find($matchingRoleId);
                $roleName = $role ? $role->name : 'Unknown';
                return [
                    'success' => false,
                    'finalized' => false,
                    'approvals' => $this->approvalsCount(),
                    'rejections' => $this->rejectionsCount(),
                    'required' => $this->requiredApprovals(),
                    'status' => $this->status,
                    'message' => "A user with role '{$roleName}' has already voted on this level.",
                ];
            }
        }

        $this->votes()->create([
            'user_id' => $approver->id,
            'decision' => 'rejected',
            'comment' => $comment,
        ]);

        $approvals = $this->approvalsCount();
        $rejections = $this->rejectionsCount();
        $required = $this->requiredApprovals();

        if ($rejections >= $required) {
            $this->update([
                'status' => 'rejected',
                // Keep legacy fields for existing UI; store the last approver/comment.
                'approver_id' => $approver->id,
                'approver_comment' => $comment,
                'rejected_at' => now(),
            ]);

            // Reject the entire request only when this level is finalized as rejected
            $this->approvalRequest->reject($comment);

            return [
                'success' => true,
                'finalized' => true,
                'approvals' => $approvals,
                'rejections' => $rejections,
                'required' => $required,
                'status' => 'rejected',
            ];
        }

        return [
            'success' => true,
            'finalized' => false,
            'approvals' => $approvals,
            'rejections' => $rejections,
            'required' => $required,
            'status' => 'pending',
        ];
    }

    /**
     * Scope for pending levels
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved levels
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected levels
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}

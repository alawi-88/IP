<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ApprovalWorkflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalWorkflowPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']) || $user->hasPermissionTo('view ApprovalPolicies');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ApprovalWorkflow $approvalWorkflow): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']) || $user->hasPermissionTo('view ApprovalPolicies');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']) || $user->hasPermissionTo('create ApprovalPolicies');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ApprovalWorkflow $approvalWorkflow): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']) || $user->hasPermissionTo('update ApprovalPolicies');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ApprovalWorkflow $approvalWorkflow): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']) || $user->hasPermissionTo('delete ApprovalPolicies');
    }


    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ApprovalWorkflow $approvalWorkflow): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']) || $user->hasPermissionTo('delete ApprovalPolicies');
    }
}

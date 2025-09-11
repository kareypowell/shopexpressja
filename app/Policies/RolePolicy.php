<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->isSuperAdmin() && $role->canBeDeleted();
    }

    /**
     * Determine whether the user can manage role assignments.
     */
    public function manageAssignments(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view role audit trail.
     */
    public function viewAuditTrail(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
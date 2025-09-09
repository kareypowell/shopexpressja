<?php

namespace App\Policies;

use App\Models\Manifest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManifestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Manifest $manifest): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Manifest $manifest): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can edit the model (checks if manifest is open).
     */
    public function edit(User $user, Manifest $manifest): bool
    {
        return $manifest->is_open && ($user->isSuperAdmin() || $user->isAdmin());
    }

    /**
     * Determine whether the user can unlock a closed manifest.
     */
    public function unlock(User $user, Manifest $manifest): bool
    {
        return !$manifest->is_open && ($user->isSuperAdmin() || $user->isAdmin());
    }

    /**
     * Determine whether the user can view audit trail.
     */
    public function viewAudit(User $user, Manifest $manifest): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Manifest $manifest): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
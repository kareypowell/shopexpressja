<?php

namespace App\Policies;

use App\Models\ConsolidatedPackage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConsolidatedPackagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the consolidated package.
     */
    public function view(User $user, ConsolidatedPackage $consolidatedPackage): bool
    {
        // Superadmin and admin users can view any consolidated package
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        // Customers can only view their own consolidated packages
        return $user->id === $consolidatedPackage->customer_id;
    }

    /**
     * Determine whether the user can create consolidated packages.
     */
    public function create(User $user): bool
    {
        // Superadmin and admin users can create consolidated packages
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the consolidated package.
     */
    public function update(User $user, ConsolidatedPackage $consolidatedPackage): bool
    {
        // Superadmin and admin users can update consolidated packages
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the consolidated package.
     */
    public function delete(User $user, ConsolidatedPackage $consolidatedPackage): bool
    {
        // Superadmin and admin users can delete consolidated packages
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can unconsolidate the consolidated package.
     */
    public function unconsolidate(User $user, ConsolidatedPackage $consolidatedPackage): bool
    {
        // Superadmin and admin users can unconsolidate packages
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the consolidation history.
     */
    public function viewHistory(User $user, ConsolidatedPackage $consolidatedPackage): bool
    {
        // Same as view permission - admin can view any, customers can view their own
        return $this->view($user, $consolidatedPackage);
    }

    /**
     * Determine whether the user can export audit trail.
     */
    public function exportAuditTrail(User $user, ConsolidatedPackage $consolidatedPackage): bool
    {
        // Same as view permission - admin can export any, customers can export their own
        return $this->view($user, $consolidatedPackage);
    }
}
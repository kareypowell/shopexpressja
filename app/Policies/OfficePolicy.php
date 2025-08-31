<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any offices.
     */
    public function viewAny(User $user)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can view the office.
     */
    public function view(User $user, Office $office)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can create offices.
     */
    public function create(User $user)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can update the office.
     */
    public function update(User $user, Office $office)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the office.
     */
    public function delete(User $user, Office $office)
    {
        // Only admin and superadmin can delete offices
        if (!$user->hasRole(['admin', 'superadmin'])) {
            return false;
        }

        // Check for relationship dependencies before allowing deletion
        $hasPackages = $office->packages()->exists();
        $hasProfiles = $office->profiles()->exists();

        // Prevent deletion if office has associated records
        return !($hasPackages || $hasProfiles);
    }
}
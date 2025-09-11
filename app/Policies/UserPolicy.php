<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Superadmin: Full access to all users
     * Admin: Access to customer users only
     * Customer: No access to user lists
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     * Superadmin: Can view any user
     * Admin: Can view customer users and their own profile
     * Customer: Can only view their own profile
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $targetUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $authenticatedUser, User $targetUser)
    {
        // Superadmin can view anyone
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can view customers and their own profile
        if ($authenticatedUser->isAdmin()) {
            return $targetUser->isCustomer() || $authenticatedUser->id === $targetUser->id;
        }

        // Customer can only view their own profile
        if ($authenticatedUser->isCustomer()) {
            return $authenticatedUser->id === $targetUser->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     * Superadmin: Can create any user type
     * Admin: Can create customer users only
     * Customer: Cannot create users
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     * Superadmin: Can update any user
     * Admin: Can update customer users and their own profile
     * Customer: Can only update their own profile
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $targetUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $authenticatedUser, User $targetUser)
    {
        // Superadmin can update anyone
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can update customers and their own profile
        if ($authenticatedUser->isAdmin()) {
            return $targetUser->isCustomer() || $authenticatedUser->id === $targetUser->id;
        }

        // Customer can only update their own profile
        if ($authenticatedUser->isCustomer()) {
            return $authenticatedUser->id === $targetUser->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Superadmin: Can delete any user (except themselves)
     * Admin: Can delete customer users only (not themselves)
     * Customer: Cannot delete users
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $targetUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $authenticatedUser, User $targetUser)
    {
        // Users cannot delete themselves
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        // Superadmin can delete anyone (except themselves)
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can delete customers only
        if ($authenticatedUser->isAdmin()) {
            return $targetUser->isCustomer();
        }

        // Customers cannot delete users
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     * Superadmin: Can restore any user
     * Admin: Can restore customer users only
     * Customer: Cannot restore users
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $targetUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $authenticatedUser, User $targetUser)
    {
        // Superadmin can restore anyone
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can restore customers only
        if ($authenticatedUser->isAdmin()) {
            return $targetUser->isCustomer();
        }

        // Customers cannot restore users
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only superadmin can permanently delete users
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $authenticatedUser, User $user)
    {
        return $authenticatedUser->isSuperAdmin() && $authenticatedUser->id !== $user->id;
    }

    /**
     * Determine whether the user can change roles.
     * Superadmin: Can change any user's role
     * Admin: Can change customer roles only
     * Customer: Cannot change roles
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $targetUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function changeRole(User $authenticatedUser, User $targetUser)
    {
        // Users cannot change their own role
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        // Superadmin can change anyone's role
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can change customer roles only
        if ($authenticatedUser->isAdmin()) {
            return $targetUser->isCustomer();
        }

        // Customers cannot change roles
        return false;
    }

    /**
     * Determine whether the user can manage user roles.
     * Only superadmin and admin can manage user roles
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manageRoles(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view user statistics.
     * Only superadmin and admin can view user statistics
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewStatistics(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create users with specific roles.
     * Superadmin: Can create any role
     * Admin: Can create customer roles only
     *
     * @param  \App\Models\User  $user
     * @param  string  $roleName
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function createWithRole(User $user, string $roleName)
    {
        // Superadmin can create any role
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can create customer roles only
        if ($user->isAdmin()) {
            return $roleName === 'customer';
        }

        return false;
    }
}

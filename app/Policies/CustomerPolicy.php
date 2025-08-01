<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customer models.
     * Only superadmin and admin users can view customer lists.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the customer model.
     * Superadmin and admin users can view any customer.
     * Customers can only view their own profile.
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $customer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $authenticatedUser, User $customer)
    {
        // Only allow viewing of customer accounts
        if (!$customer->isCustomer()) {
            return false;
        }

        // Superadmin can view any customer
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can view any customer
        if ($authenticatedUser->isAdmin()) {
            return true;
        }

        // Customer can only view their own profile
        if ($authenticatedUser->isCustomer()) {
            return $authenticatedUser->id === $customer->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create customer models.
     * Only superadmin and admin users can create customers.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the customer model.
     * Superadmin and admin users can update any customer.
     * Customers can only update their own profile.
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $customer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $authenticatedUser, User $customer)
    {
        // Only allow updating of customer accounts
        if (!$customer->isCustomer()) {
            return false;
        }

        // Superadmin can update any customer
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can update any customer
        if ($authenticatedUser->isAdmin()) {
            return true;
        }

        // Customer can only update their own profile
        if ($authenticatedUser->isCustomer()) {
            return $authenticatedUser->id === $customer->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the customer model.
     * Only superadmin and admin users can delete customers.
     * Users cannot delete themselves.
     * Superadmin accounts cannot be deleted.
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $customer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $authenticatedUser, User $customer)
    {
        // Only allow deletion of customer accounts
        if (!$customer->isCustomer()) {
            return false;
        }

        // Users cannot delete themselves
        if ($authenticatedUser->id === $customer->id) {
            return false;
        }

        // Superadmin accounts cannot be deleted (extra safety check)
        if ($customer->isSuperAdmin()) {
            return false;
        }

        // Only superadmin and admin can delete customers
        return $authenticatedUser->isSuperAdmin() || $authenticatedUser->isAdmin();
    }

    /**
     * Determine whether the user can restore the customer model.
     * Only superadmin and admin users can restore customers.
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $customer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $authenticatedUser, User $customer)
    {
        // Only allow restoration of customer accounts
        if (!$customer->isCustomer()) {
            return false;
        }

        // Only superadmin and admin can restore customers
        return $authenticatedUser->isSuperAdmin() || $authenticatedUser->isAdmin();
    }

    /**
     * Determine whether the user can view customer financial information.
     * Only superadmin and admin users can view customer financial data.
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $customer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewFinancials(User $authenticatedUser, User $customer)
    {
        // Only allow viewing financials of customer accounts
        if (!$customer->isCustomer()) {
            return false;
        }

        // Superadmin can view any customer's financials
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can view any customer's financials
        if ($authenticatedUser->isAdmin()) {
            return true;
        }

        // Customer can view their own financials
        if ($authenticatedUser->isCustomer()) {
            return $authenticatedUser->id === $customer->id;
        }

        return false;
    }

    /**
     * Determine whether the user can view customer package history.
     * Only superadmin and admin users can view customer package history.
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $customer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewPackages(User $authenticatedUser, User $customer)
    {
        // Only allow viewing packages of customer accounts
        if (!$customer->isCustomer()) {
            return false;
        }

        // Superadmin can view any customer's packages
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        // Admin can view any customer's packages
        if ($authenticatedUser->isAdmin()) {
            return true;
        }

        // Customer can view their own packages
        if ($authenticatedUser->isCustomer()) {
            return $authenticatedUser->id === $customer->id;
        }

        return false;
    }

    /**
     * Determine whether the user can perform bulk operations on customers.
     * Only superadmin and admin users can perform bulk operations.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function bulkOperations(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can export customer data.
     * Only superadmin and admin users can export customer data.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function export(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can send emails to customers.
     * Only superadmin and admin users can send emails to customers.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function sendEmail(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view deleted customers.
     * Only superadmin and admin users can view deleted customers.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewDeleted(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the customer model.
     * Only superadmin users can permanently delete customers.
     *
     * @param  \App\Models\User  $authenticatedUser
     * @param  \App\Models\User  $customer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $authenticatedUser, User $customer)
    {
        // Only allow force deletion of customer accounts
        if (!$customer->isCustomer()) {
            return false;
        }

        // Users cannot force delete themselves
        if ($authenticatedUser->id === $customer->id) {
            return false;
        }

        // Only superadmin can permanently delete customers
        return $authenticatedUser->isSuperAdmin();
    }
}
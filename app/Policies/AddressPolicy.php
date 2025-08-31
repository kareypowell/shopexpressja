<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any addresses.
     */
    public function viewAny(User $user)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can view the address.
     */
    public function view(User $user, Address $address)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can create addresses.
     */
    public function create(User $user)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can update the address.
     */
    public function update(User $user, Address $address)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the address.
     */
    public function delete(User $user, Address $address)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }
}
<?php

namespace App\Policies;

use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any transactions.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        // Admin and superadmin can view all transactions
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can view the transaction.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CustomerTransaction  $transaction
     * @return mixed
     */
    public function view(User $user, CustomerTransaction $transaction)
    {
        // Admin and superadmin can view all transactions
        if ($user->hasRole(['admin', 'superadmin'])) {
            return true;
        }

        // Customers can only view their own transactions
        return $user->hasRole('customer') && $transaction->user_id === $user->id;
    }

    /**
     * Determine whether the user can create transactions.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        // Only admin and superadmin can create transactions
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can update the transaction.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CustomerTransaction  $transaction
     * @return mixed
     */
    public function update(User $user, CustomerTransaction $transaction)
    {
        // Only admin and superadmin can update transactions
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the transaction.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CustomerTransaction  $transaction
     * @return mixed
     */
    public function delete(User $user, CustomerTransaction $transaction)
    {
        // Only superadmin can delete transactions
        return $user->hasRole('superadmin');
    }
}
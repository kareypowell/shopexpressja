<?php

namespace App\Policies;

use App\Models\BroadcastMessage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BroadcastMessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any broadcast messages.
     */
    public function viewAny(User $user)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can view the broadcast message.
     */
    public function view(User $user, BroadcastMessage $broadcastMessage)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can create broadcast messages.
     */
    public function create(User $user)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can update the broadcast message.
     */
    public function update(User $user, BroadcastMessage $broadcastMessage)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the broadcast message.
     */
    public function delete(User $user, BroadcastMessage $broadcastMessage)
    {
        return $user->hasRole(['admin', 'superadmin']);
    }
}
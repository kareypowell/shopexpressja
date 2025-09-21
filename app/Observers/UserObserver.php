<?php

namespace App\Observers;

use App\Events\RoleChanged;
use App\Models\User;
use App\Services\CustomerCacheInvalidationService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected CustomerCacheInvalidationService $cacheInvalidationService;

    public function __construct(CustomerCacheInvalidationService $cacheInvalidationService)
    {
        $this->cacheInvalidationService = $cacheInvalidationService;
    }

    /**
     * Handle the User "created" event.
     *
     * @param User $user
     * @return void
     */
    public function created(User $user)
    {
        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerCreation($user);
        }
    }

    /**
     * Handle the User "updating" event to capture role changes.
     *
     * @param User $user
     * @return void
     */
    public function updating(User $user)
    {
        // Check if role_id is being changed
        if ($user->isDirty('role_id')) {
            $oldRoleId = $user->getOriginal('role_id');
            $newRoleId = $user->role_id;
            
            // Store the role change data for the updated event
            $user->_roleChangeData = [
                'old_role_id' => $oldRoleId,
                'new_role_id' => $newRoleId,
            ];
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user)
    {
        // Handle role change if it occurred
        if (isset($user->_roleChangeData)) {
            $roleChangeData = $user->_roleChangeData;
            
            // Fire the role changed event
            event(new RoleChanged(
                $user,
                $roleChangeData['old_role_id'],
                $roleChangeData['new_role_id']
            ));
            
            // Clean up the temporary data
            unset($user->_roleChangeData);
        }

        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerProfileUpdate($user);
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function deleted(User $user)
    {
        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerDeletion($user);
        }
    }

    /**
     * Handle the User "restored" event.
     *
     * @param User $user
     * @return void
     */
    public function restored(User $user)
    {
        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerRestoration($user);
        }
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerDeletion($user);
        }
    }
}
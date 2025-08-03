<?php

namespace App\Observers;

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
     * Handle the User "updated" event.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user)
    {
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
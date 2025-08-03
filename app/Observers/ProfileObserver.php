<?php

namespace App\Observers;

use App\Models\Profile;
use App\Services\CustomerCacheInvalidationService;
use Illuminate\Support\Facades\Log;

class ProfileObserver
{
    protected CustomerCacheInvalidationService $cacheInvalidationService;

    public function __construct(CustomerCacheInvalidationService $cacheInvalidationService)
    {
        $this->cacheInvalidationService = $cacheInvalidationService;
    }

    /**
     * Handle the Profile "created" event.
     *
     * @param Profile $profile
     * @return void
     */
    public function created(Profile $profile)
    {
        if ($profile->user && $profile->user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerProfileUpdate($profile->user);
        }
    }

    /**
     * Handle the Profile "updated" event.
     *
     * @param Profile $profile
     * @return void
     */
    public function updated(Profile $profile)
    {
        if ($profile->user && $profile->user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerProfileUpdate($profile->user);
        }
    }

    /**
     * Handle the Profile "deleted" event.
     *
     * @param Profile $profile
     * @return void
     */
    public function deleted(Profile $profile)
    {
        if ($profile->user && $profile->user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerProfileUpdate($profile->user);
        }
    }

    /**
     * Handle the Profile "restored" event.
     *
     * @param Profile $profile
     * @return void
     */
    public function restored(Profile $profile)
    {
        if ($profile->user && $profile->user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerProfileUpdate($profile->user);
        }
    }
}
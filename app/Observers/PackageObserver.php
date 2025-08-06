<?php

namespace App\Observers;

use App\Models\Package;
use App\Services\CustomerCacheInvalidationService;
use Illuminate\Support\Facades\Log;

class PackageObserver
{
    protected CustomerCacheInvalidationService $cacheInvalidationService;

    public function __construct(CustomerCacheInvalidationService $cacheInvalidationService)
    {
        $this->cacheInvalidationService = $cacheInvalidationService;
    }

    /**
     * Handle the Package "created" event.
     *
     * @param Package $package
     * @return void
     */
    public function created(Package $package)
    {
        $this->cacheInvalidationService->handlePackageCreation($package);
    }

    /**
     * Handle the Package "updated" event.
     *
     * @param Package $package
     * @return void
     */
    public function updated(Package $package)
    {
        // Get the changes that were made
        $changes = $package->getChanges();
        $this->cacheInvalidationService->handlePackageUpdate($package, $changes);
    }

    /**
     * Handle the Package "deleted" event.
     *
     * @param Package $package
     * @return void
     */
    public function deleted(Package $package)
    {
        $this->cacheInvalidationService->handlePackageDeletion($package);
    }

    /**
     * Handle the Package "restored" event.
     *
     * @param Package $package
     * @return void
     */
    public function restored(Package $package)
    {
        $this->cacheInvalidationService->handlePackageCreation($package);
    }

    /**
     * Handle the Package "force deleted" event.
     *
     * @param Package $package
     * @return void
     */
    public function forceDeleted(Package $package)
    {
        $this->cacheInvalidationService->handlePackageDeletion($package);
    }
}
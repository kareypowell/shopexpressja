<?php

namespace App\Observers;

use App\Models\Package;
use App\Services\CustomerCacheInvalidationService;
use App\Services\ManifestLockService;
use Illuminate\Support\Facades\Log;

class PackageObserver
{
    protected CustomerCacheInvalidationService $cacheInvalidationService;
    protected ManifestLockService $manifestLockService;

    public function __construct(
        CustomerCacheInvalidationService $cacheInvalidationService,
        ManifestLockService $manifestLockService
    ) {
        $this->cacheInvalidationService = $cacheInvalidationService;
        $this->manifestLockService = $manifestLockService;
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

        // Check for automatic manifest closure when package status changes to "delivered"
        $this->handleManifestAutoClosure($package);
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

    /**
     * Handle automatic manifest closure when package status changes to "delivered"
     *
     * @param Package $package
     * @return void
     */
    protected function handleManifestAutoClosure(Package $package): void
    {
        try {
            // Only process if status was changed to "delivered"
            if (!$package->isDirty('status') || $package->status->value !== 'delivered') {
                return;
            }

            // Only process if package has a manifest
            if (!$package->manifest_id || !$package->manifest) {
                return;
            }

            // Load the manifest with packages to avoid N+1 queries
            $manifest = $package->manifest()->with('packages')->first();
            
            if (!$manifest) {
                Log::warning('Package has manifest_id but manifest not found', [
                    'package_id' => $package->id,
                    'manifest_id' => $package->manifest_id,
                ]);
                return;
            }

            // Attempt auto-closure
            $closed = $this->manifestLockService->autoCloseIfComplete($manifest);

            if ($closed) {
                Log::info('Manifest auto-closed after package delivery', [
                    'manifest_id' => $manifest->id,
                    'manifest_name' => $manifest->name,
                    'triggering_package_id' => $package->id,
                    'triggering_package_tracking' => $package->tracking_number,
                    'total_packages' => $manifest->packages()->count(),
                    'delivered_packages' => $manifest->packages()->where('status', 'delivered')->count(),
                ]);
            }

        } catch (\Exception $e) {
            // Log error but don't throw to avoid breaking package updates
            Log::error('Error during manifest auto-closure check', [
                'package_id' => $package->id,
                'package_tracking' => $package->tracking_number,
                'manifest_id' => $package->manifest_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
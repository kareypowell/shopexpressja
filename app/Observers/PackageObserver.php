<?php

namespace App\Observers;

use App\Models\Package;
use App\Services\CustomerCacheInvalidationService;
use App\Services\ManifestLockService;
use App\Services\ManifestSummaryCacheService;
use Illuminate\Support\Facades\Log;

class PackageObserver
{
    protected CustomerCacheInvalidationService $cacheInvalidationService;
    protected ManifestLockService $manifestLockService;
    protected ManifestSummaryCacheService $manifestCacheService;

    public function __construct(
        CustomerCacheInvalidationService $cacheInvalidationService,
        ManifestLockService $manifestLockService,
        ManifestSummaryCacheService $manifestCacheService
    ) {
        $this->cacheInvalidationService = $cacheInvalidationService;
        $this->manifestLockService = $manifestLockService;
        $this->manifestCacheService = $manifestCacheService;
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
        $this->invalidateManifestCache($package);
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
        $this->invalidateManifestCache($package);

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
        $this->invalidateManifestCache($package);
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
        $this->invalidateManifestCache($package);
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
        $this->invalidateManifestCache($package);
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

    /**
     * Invalidate manifest cache when package changes
     *
     * @param Package $package
     * @return void
     */
    protected function invalidateManifestCache(Package $package): void
    {
        try {
            // Only invalidate if package belongs to a manifest
            if ($package->manifest_id && $package->manifest) {
                $this->manifestCacheService->invalidateManifestCache($package->manifest);
                
                Log::info('Invalidated manifest cache due to package change', [
                    'package_id' => $package->id,
                    'package_tracking' => $package->tracking_number,
                    'manifest_id' => $package->manifest_id,
                    'manifest_name' => $package->manifest->name ?? 'Unknown',
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't throw to avoid breaking package operations
            Log::error('Failed to invalidate manifest cache', [
                'package_id' => $package->id,
                'package_tracking' => $package->tracking_number,
                'manifest_id' => $package->manifest_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
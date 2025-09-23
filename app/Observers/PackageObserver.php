<?php

namespace App\Observers;

use App\Models\Package;
use App\Services\AuditService;
use App\Services\CustomerCacheInvalidationService;
use App\Services\ManifestLockService;
use App\Services\ManifestSummaryCacheService;
use Illuminate\Support\Facades\Log;

class PackageObserver
{
    protected CustomerCacheInvalidationService $cacheInvalidationService;
    protected ManifestLockService $manifestLockService;
    protected ManifestSummaryCacheService $manifestCacheService;
    protected AuditService $auditService;

    public function __construct(
        CustomerCacheInvalidationService $cacheInvalidationService,
        ManifestLockService $manifestLockService,
        ManifestSummaryCacheService $manifestCacheService,
        AuditService $auditService
    ) {
        $this->cacheInvalidationService = $cacheInvalidationService;
        $this->manifestLockService = $manifestLockService;
        $this->manifestCacheService = $manifestCacheService;
        $this->auditService = $auditService;
    }

    /**
     * Handle the Package "created" event.
     *
     * @param Package $package
     * @return void
     */
    public function created(Package $package)
    {
        // Log package creation to audit system
        $this->auditService->logModelCreated($package);
        
        // Log business action for package creation
        $this->auditService->logBusinessAction('package_created', $package, [
            'tracking_number' => $package->tracking_number,
            'customer_id' => $package->user_id,
            'manifest_id' => $package->manifest_id,
            'status' => $package->status->value ?? null,
            'description' => $package->description,
        ]);

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
        $originalValues = $package->getOriginal();
        
        // Log package update to audit system
        $this->auditService->logModelUpdated($package, $originalValues);
        
        // Log specific business actions for important changes
        if (isset($changes['status'])) {
            $oldStatus = $originalValues['status'] ?? null;
            $newStatus = $package->status->value ?? null;
            
            $this->auditService->logPackageStatusChange(
                $package, 
                $oldStatus, 
                $newStatus
            );
        }
        
        // Log consolidation changes
        if (isset($changes['consolidated_package_id'])) {
            $action = $package->consolidated_package_id ? 'package_consolidated' : 'package_unconsolidated';
            $this->auditService->logBusinessAction($action, $package, [
                'tracking_number' => $package->tracking_number,
                'customer_id' => $package->user_id,
                'old_consolidated_package_id' => $originalValues['consolidated_package_id'] ?? null,
                'new_consolidated_package_id' => $package->consolidated_package_id,
            ]);
        }
        
        // Log manifest assignment changes
        if (isset($changes['manifest_id'])) {
            $this->auditService->logBusinessAction('package_manifest_changed', $package, [
                'tracking_number' => $package->tracking_number,
                'customer_id' => $package->user_id,
                'old_manifest_id' => $originalValues['manifest_id'] ?? null,
                'new_manifest_id' => $package->manifest_id,
            ]);
        }
        
        // Log fee changes
        $feeFields = ['clearance_fee', 'storage_fee', 'delivery_fee', 'freight_price'];
        $feeChanges = array_intersect_key($changes, array_flip($feeFields));
        if (!empty($feeChanges)) {
            $this->auditService->logBusinessAction('package_fees_updated', $package, [
                'tracking_number' => $package->tracking_number,
                'customer_id' => $package->user_id,
                'fee_changes' => $feeChanges,
                'old_total_cost' => $this->calculateTotalCost($originalValues),
                'new_total_cost' => $package->total_cost,
            ]);
        }

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
        // Log package deletion to audit system
        $this->auditService->logModelDeleted($package);
        
        // Log business action for package deletion
        $this->auditService->logBusinessAction('package_deleted', $package, [
            'tracking_number' => $package->tracking_number,
            'customer_id' => $package->user_id,
            'manifest_id' => $package->manifest_id,
            'consolidated_package_id' => $package->consolidated_package_id,
            'status' => $package->status->value ?? null,
        ]);

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
        // Log package restoration to audit system
        $this->auditService->logBusinessAction('package_restored', $package, [
            'tracking_number' => $package->tracking_number,
            'customer_id' => $package->user_id,
            'manifest_id' => $package->manifest_id,
            'status' => $package->status->value ?? null,
        ]);

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
        // Log package force deletion to audit system
        $this->auditService->logBusinessAction('package_force_deleted', $package, [
            'tracking_number' => $package->tracking_number,
            'customer_id' => $package->user_id,
            'manifest_id' => $package->manifest_id,
            'status' => $package->status->value ?? null,
        ]);

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

    /**
     * Calculate total cost from package attributes
     *
     * @param array $attributes
     * @return float
     */
    protected function calculateTotalCost(array $attributes): float
    {
        return ($attributes['freight_price'] ?? 0) +
               ($attributes['clearance_fee'] ?? 0) +
               ($attributes['storage_fee'] ?? 0) +
               ($attributes['delivery_fee'] ?? 0);
    }
}
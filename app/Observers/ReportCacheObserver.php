<?php

namespace App\Observers;

use App\Models\Package;
use App\Models\Manifest;
use App\Models\CustomerTransaction;
use App\Models\User;
use App\Models\PackageDistribution;
use App\Models\ConsolidatedPackage;
use App\Services\ReportCacheService;
use App\Jobs\WarmReportCacheJob;
use Illuminate\Support\Facades\Log;

class ReportCacheObserver
{
    protected ReportCacheService $reportCacheService;
    protected bool $shouldWarmCache;

    public function __construct(ReportCacheService $reportCacheService)
    {
        $this->reportCacheService = $reportCacheService;
        $this->shouldWarmCache = config('reports.auto_warm_cache', true);
    }

    /**
     * Handle model "created" events
     */
    public function created($model): void
    {
        $this->handleModelEvent($model, 'created');
    }

    /**
     * Handle model "updated" events
     */
    public function updated($model): void
    {
        $this->handleModelEvent($model, 'updated');
    }

    /**
     * Handle model "deleted" events
     */
    public function deleted($model): void
    {
        $this->handleModelEvent($model, 'deleted');
    }

    /**
     * Handle model "restored" events
     */
    public function restored($model): void
    {
        $this->handleModelEvent($model, 'restored');
    }

    /**
     * Route model events to appropriate handlers
     */
    private function handleModelEvent($model, string $event): void
    {
        switch (get_class($model)) {
            case Package::class:
                $this->invalidatePackageRelatedCache($model, $event);
                break;
            case Manifest::class:
                $this->invalidateManifestRelatedCache($model, $event);
                break;
            case CustomerTransaction::class:
                $this->invalidateTransactionRelatedCache($model, $event);
                break;
            case User::class:
                if (in_array($event, ['updated', 'deleted'])) {
                    $this->invalidateUserRelatedCache($model, $event);
                }
                break;
            case PackageDistribution::class:
                $this->invalidateDistributionRelatedCache($model, $event);
                break;
            case ConsolidatedPackage::class:
                $this->invalidateConsolidationRelatedCache($model, $event);
                break;
        }
    }

    /**
     * Invalidate cache related to package changes
     */
    private function invalidatePackageRelatedCache(Package $package, string $event): void
    {
        try {
            // Invalidate all package-related report caches
            $this->reportCacheService->invalidateModelCache('package', $package->id);

            // Log cache invalidation
            Log::info('Report cache invalidated due to package event', [
                'event' => $event,
                'package_id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'user_id' => $package->user_id,
                'manifest_id' => $package->manifest_id
            ]);

            // If package status changed to delivered, invalidate completion metrics
            if ($event === 'updated' && $package->isDirty('status') && $package->status === 'delivered') {
                $this->reportCacheService->invalidateReportCache('report:manifest:completion:*');
                $this->reportCacheService->invalidateReportCache('report:dashboard:*');
                $this->scheduleWarmupIfNeeded(['manifest', 'dashboard']);
            }

            // If package financial data changed, invalidate financial reports
            $financialFields = ['freight_price', 'clearance_fee', 'storage_fee', 'delivery_fee'];
            if ($event === 'updated' && $package->isDirty($financialFields)) {
                $this->reportCacheService->invalidateReportCache('report:sales:*');
                $this->reportCacheService->invalidateReportCache('report:financial:*');
                $this->scheduleWarmupIfNeeded(['sales', 'financial']);
            }

            // Schedule warmup for new packages
            if ($event === 'created') {
                $this->scheduleWarmupIfNeeded(['sales', 'manifest', 'dashboard']);
            }

        } catch (\Exception $e) {
            Log::error('Failed to invalidate package-related report cache', [
                'event' => $event,
                'package_id' => $package->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate cache related to manifest changes
     */
    private function invalidateManifestRelatedCache(Manifest $manifest, string $event): void
    {
        try {
            // Invalidate manifest-related report caches
            $this->reportCacheService->invalidateModelCache('manifest', $manifest->id);

            // Log cache invalidation
            Log::info('Report cache invalidated due to manifest event', [
                'event' => $event,
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'manifest_type' => $manifest->type
            ]);

            // If manifest status changed (opened/closed), invalidate efficiency metrics
            if ($event === 'updated' && $manifest->isDirty('is_open')) {
                $this->reportCacheService->invalidateReportCache('report:manifest:efficiency:*');
                $this->reportCacheService->invalidateReportCache('report:analytics:*');
                $this->scheduleWarmupIfNeeded(['manifest']);
            }

            // If shipment date changed, affects processing time calculations
            if ($event === 'updated' && $manifest->isDirty('shipment_date')) {
                $this->reportCacheService->invalidateReportCache('report:manifest:*');
                $this->scheduleWarmupIfNeeded(['manifest']);
            }

            // Schedule warmup for new manifests
            if ($event === 'created') {
                $this->scheduleWarmupIfNeeded(['manifest', 'dashboard']);
            }

        } catch (\Exception $e) {
            Log::error('Failed to invalidate manifest-related report cache', [
                'event' => $event,
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate cache related to customer transaction changes
     */
    private function invalidateTransactionRelatedCache(CustomerTransaction $transaction, string $event): void
    {
        try {
            // Invalidate transaction-related report caches
            $this->reportCacheService->invalidateModelCache('customer_transaction', $transaction->id);

            // Log cache invalidation
            Log::info('Report cache invalidated due to transaction event', [
                'event' => $event,
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'type' => $transaction->type,
                'amount' => $transaction->amount
            ]);

            // Always invalidate financial and sales reports for transaction changes
            $this->reportCacheService->invalidateReportCache('report:sales:*');
            $this->reportCacheService->invalidateReportCache('report:financial:*');
            $this->reportCacheService->invalidateReportCache('report:customer:*');

            // Payment transactions are more significant for financial reports
            if ($transaction->type === 'payment') {
                $this->scheduleWarmupIfNeeded(['sales', 'financial', 'dashboard']);
            } else {
                $this->scheduleWarmupIfNeeded(['financial']);
            }

        } catch (\Exception $e) {
            Log::error('Failed to invalidate transaction-related report cache', [
                'event' => $event,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate cache related to user changes
     */
    private function invalidateUserRelatedCache(User $user, string $event): void
    {
        try {
            // Invalidate user-specific report caches
            $this->reportCacheService->invalidateModelCache('user', $user->id);

            // Log cache invalidation
            Log::info('Report cache invalidated due to user event', [
                'event' => $event,
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Account balance changes affect financial reports
            if ($event === 'updated' && $user->isDirty('account_balance')) {
                $this->reportCacheService->invalidateReportCache('report:financial:*');
                $this->reportCacheService->invalidateReportCache('report:customer:*');
                $this->scheduleWarmupIfNeeded(['customer', 'financial', 'dashboard']);
            }

        } catch (\Exception $e) {
            Log::error('Failed to invalidate user-related report cache', [
                'event' => $event,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate cache related to package distribution changes
     */
    private function invalidateDistributionRelatedCache(PackageDistribution $distribution, string $event): void
    {
        try {
            // Package distributions affect sales and financial reports significantly
            $this->reportCacheService->invalidateReportCache('report:sales:*');
            $this->reportCacheService->invalidateReportCache('report:financial:*');
            $this->reportCacheService->invalidateReportCache('report:customer:*');
            $this->reportCacheService->invalidateReportCache('report:dashboard:*');

            // Log cache invalidation
            Log::info('Report cache invalidated due to distribution event', [
                'event' => $event,
                'distribution_id' => $distribution->id,
                'customer_id' => $distribution->customer_id ?? null,
                'total_amount' => $distribution->total_amount ?? null
            ]);

            // Schedule warmup for distribution changes
            $this->scheduleWarmupIfNeeded(['sales', 'financial', 'dashboard']);

        } catch (\Exception $e) {
            Log::error('Failed to invalidate distribution-related report cache', [
                'event' => $event,
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate cache related to consolidated package changes
     */
    private function invalidateConsolidationRelatedCache(ConsolidatedPackage $consolidatedPackage, string $event): void
    {
        try {
            // Consolidated packages affect manifest and customer reports
            $this->reportCacheService->invalidateReportCache('report:manifest:*');
            $this->reportCacheService->invalidateReportCache('report:customer:*');
            $this->reportCacheService->invalidateReportCache('report:dashboard:*');

            // Log cache invalidation
            Log::info('Report cache invalidated due to consolidation event', [
                'event' => $event,
                'consolidated_package_id' => $consolidatedPackage->id,
                'user_id' => $consolidatedPackage->user_id ?? null,
                'status' => $consolidatedPackage->status ?? null
            ]);

            // Schedule warmup for consolidation changes
            if ($event === 'updated' && $consolidatedPackage->isDirty('status')) {
                $this->scheduleWarmupIfNeeded(['manifest', 'customer', 'dashboard']);
            } elseif ($event === 'created') {
                $this->scheduleWarmupIfNeeded(['manifest', 'customer', 'dashboard']);
            }

        } catch (\Exception $e) {
            Log::error('Failed to invalidate consolidation-related report cache', [
                'event' => $event,
                'consolidated_package_id' => $consolidatedPackage->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Schedule cache warmup if needed
     */
    private function scheduleWarmupIfNeeded(array $cacheTypes): void
    {
        if (!$this->shouldWarmCache) {
            return;
        }

        try {
            // Dispatch warmup job with delay to avoid overwhelming the system
            WarmReportCacheJob::dispatch($cacheTypes)
                ->delay(now()->addMinutes(2))
                ->onQueue('reports');
                
            Log::debug('Scheduled cache warmup', [
                'cache_types' => $cacheTypes,
                'delay_minutes' => 2
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to schedule cache warmup', [
                'cache_types' => $cacheTypes,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enable or disable automatic cache warming
     */
    public function setAutoWarmCache(bool $enabled): void
    {
        $this->shouldWarmCache = $enabled;
    }
}
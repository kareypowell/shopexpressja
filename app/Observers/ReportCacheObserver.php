<?php

namespace App\Observers;

use App\Models\Package;
use App\Models\Manifest;
use App\Models\CustomerTransaction;
use App\Models\User;
use App\Services\ReportCacheService;
use Illuminate\Support\Facades\Log;

class ReportCacheObserver
{
    protected ReportCacheService $reportCacheService;

    public function __construct(ReportCacheService $reportCacheService)
    {
        $this->reportCacheService = $reportCacheService;
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
            if ($event === 'updated' && $package->isDirty('status') && $package->status->value === 'delivered') {
                $this->reportCacheService->invalidateReportCache('report:manifest:completion:*');
                $this->reportCacheService->invalidateReportCache('report:dashboard:*');
            }

            // If package financial data changed, invalidate financial reports
            $financialFields = ['freight_price', 'clearance_fee', 'storage_fee', 'delivery_fee'];
            if ($event === 'updated' && $package->isDirty($financialFields)) {
                $this->reportCacheService->invalidateReportCache('report:sales:*');
                $this->reportCacheService->invalidateReportCache('report:financial:*');
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

        } catch (\Exception $e) {
            Log::error('Failed to invalidate user-related report cache', [
                'event' => $event,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
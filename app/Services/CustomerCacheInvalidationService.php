<?php

namespace App\Services;

use App\Models\User;
use App\Models\Package;
use Illuminate\Support\Facades\Log;

class CustomerCacheInvalidationService
{
    protected CustomerStatisticsService $statisticsService;

    public function __construct(CustomerStatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Handle customer profile update
     *
     * @param User $customer
     * @return void
     */
    public function handleCustomerProfileUpdate(User $customer): void
    {
        Log::debug("Invalidating cache for customer profile update: {$customer->id}");
        
        // Clear all customer cache since profile changes might affect calculations
        $this->statisticsService->clearCustomerCache($customer);
    }

    /**
     * Handle customer creation
     *
     * @param User $customer
     * @return void
     */
    public function handleCustomerCreation(User $customer): void
    {
        Log::info("Customer created, warming up cache: {$customer->id}");
        
        // Warm up cache for new customer (though they won't have much data initially)
        $this->statisticsService->warmUpCustomerCache($customer);
    }

    /**
     * Handle customer deletion
     *
     * @param User $customer
     * @return void
     */
    public function handleCustomerDeletion(User $customer): void
    {
        Log::info("Clearing cache for deleted customer: {$customer->id}");
        
        // Clear all cache for deleted customer
        $this->statisticsService->clearCustomerCache($customer);
    }

    /**
     * Handle customer restoration
     *
     * @param User $customer
     * @return void
     */
    public function handleCustomerRestoration(User $customer): void
    {
        Log::info("Warming up cache for restored customer: {$customer->id}");
        
        // Warm up cache for restored customer
        $this->statisticsService->warmUpCustomerCache($customer);
    }

    /**
     * Handle package creation
     *
     * @param Package $package
     * @return void
     */
    public function handlePackageCreation(Package $package): void
    {
        if ($package->user_id) {
            Log::debug("Invalidating cache for package creation - customer: {$package->user_id}");
            
            // Clear customer cache since package stats will change
            $customer = User::find($package->user_id);
            if ($customer) {
                $this->statisticsService->clearCustomerCache($customer);
            }
        }
    }

    /**
     * Handle package update
     *
     * @param Package $package
     * @param array $changes
     * @return void
     */
    public function handlePackageUpdate(Package $package, array $changes = []): void
    {
        if ($package->user_id) {
            Log::debug("Invalidating cache for package update - customer: {$package->user_id}");
            
            // Check if the update affects financial calculations
            $financialFields = ['freight_price', 'customs_duty', 'storage_fee', 'delivery_fee', 'status'];
            $affectsFinancials = !empty(array_intersect(array_keys($changes), $financialFields));
            
            $customer = User::find($package->user_id);
            if ($customer) {
                if ($affectsFinancials) {
                    // Clear all cache if financial data changed
                    $this->statisticsService->clearCustomerCache($customer);
                } else {
                    // Only clear package-related cache for non-financial updates
                    $this->statisticsService->clearCustomerCacheType($customer, 'packages');
                }
            }
        }
    }

    /**
     * Handle package deletion
     *
     * @param Package $package
     * @return void
     */
    public function handlePackageDeletion(Package $package): void
    {
        if ($package->user_id) {
            Log::debug("Invalidating cache for package deletion - customer: {$package->user_id}");
            
            // Clear customer cache since package stats will change
            $customer = User::find($package->user_id);
            if ($customer) {
                $this->statisticsService->clearCustomerCache($customer);
            }
        }
    }

    /**
     * Handle bulk package operations
     *
     * @param array $packageIds
     * @return void
     */
    public function handleBulkPackageOperations(array $packageIds): void
    {
        Log::info("Invalidating cache for bulk package operations");
        
        // Get unique customer IDs from affected packages
        $customerIds = Package::whereIn('id', $packageIds)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();
        
        if (!empty($customerIds)) {
            $this->statisticsService->clearMultipleCustomersCache($customerIds);
        }
    }

    /**
     * Handle system-wide cache refresh
     *
     * @return void
     */
    public function handleSystemCacheRefresh(): void
    {
        Log::info("Performing system-wide customer cache refresh");
        
        $this->statisticsService->clearAllCache();
    }

    /**
     * Schedule cache warm-up for active customers
     *
     * @param int $limit
     * @return void
     */
    public function scheduleActiveCustomerCacheWarmUp(int $limit = 50): void
    {
        Log::info("Scheduling cache warm-up for active customers (limit: {$limit})");
        
        // Get most active customers (those with recent packages)
        $activeCustomerIds = User::customers()
            ->whereHas('packages', function ($query) {
                $query->where('created_at', '>=', now()->subMonths(3));
            })
            ->withCount(['packages' => function ($query) {
                $query->where('created_at', '>=', now()->subMonths(3));
            }])
            ->orderBy('packages_count', 'desc')
            ->limit($limit)
            ->pluck('id')
            ->toArray();
        
        if (!empty($activeCustomerIds)) {
            $this->statisticsService->warmUpMultipleCustomersCache($activeCustomerIds);
        }
    }

    /**
     * Get cache invalidation statistics
     *
     * @return array
     */
    public function getCacheInvalidationStats(): array
    {
        return [
            'cache_performance' => $this->statisticsService->getCachePerformanceMetrics(),
            'last_system_refresh' => cache('last_system_cache_refresh'),
            'invalidation_events_today' => cache('cache_invalidation_events_' . now()->format('Y-m-d'), 0),
        ];
    }

    /**
     * Record cache invalidation event
     *
     * @param string $event
     * @return void
     */
    private function recordInvalidationEvent(string $event): void
    {
        $today = now()->format('Y-m-d');
        $key = 'cache_invalidation_events_' . $today;
        $count = cache($key, 0);
        cache([$key => $count + 1], now()->addDay());
        
        Log::debug("Cache invalidation event recorded: {$event}");
    }
}
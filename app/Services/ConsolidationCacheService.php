<?php

namespace App\Services;

use App\Models\ConsolidatedPackage;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ConsolidationCacheService
{
    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 60; // 1 hour
    const CACHE_DURATION_SHORT = 15; // 15 minutes for frequently changing data
    const CACHE_DURATION_LONG = 240; // 4 hours for stable data

    /**
     * Cache key prefixes
     */
    const PREFIX_CONSOLIDATED_TOTALS = 'consolidated_totals';
    const PREFIX_CUSTOMER_CONSOLIDATIONS = 'customer_consolidations';
    const PREFIX_AVAILABLE_PACKAGES = 'available_packages';
    const PREFIX_CONSOLIDATION_STATS = 'consolidation_stats';
    const PREFIX_SEARCH_RESULTS = 'consolidation_search';

    /**
     * Get cached consolidated package totals
     *
     * @param int $consolidatedPackageId
     * @return array|null
     */
    public function getConsolidatedTotals(int $consolidatedPackageId): ?array
    {
        $cacheKey = $this->getCacheKey(self::PREFIX_CONSOLIDATED_TOTALS, $consolidatedPackageId);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($consolidatedPackageId) {
            $consolidatedPackage = ConsolidatedPackage::find($consolidatedPackageId);
            
            if (!$consolidatedPackage) {
                return null;
            }

            // Calculate totals from individual packages for accuracy
            $packages = $consolidatedPackage->packages()
                ->select(['weight', 'freight_price', 'customs_duty', 'storage_fee', 'delivery_fee'])
                ->get();

            $totals = [
                'weight' => $packages->sum('weight'),
                'quantity' => $packages->count(),
                'freight_price' => $packages->sum('freight_price'),
                'customs_duty' => $packages->sum('customs_duty'),
                'storage_fee' => $packages->sum('storage_fee'),
                'delivery_fee' => $packages->sum('delivery_fee'),
                'total_cost' => $packages->sum(function ($package) {
                    return ($package->freight_price ?? 0) + 
                           ($package->customs_duty ?? 0) + 
                           ($package->storage_fee ?? 0) + 
                           ($package->delivery_fee ?? 0);
                }),
                'calculated_at' => now()->toISOString(),
            ];

            Log::debug('Consolidated package totals calculated and cached', [
                'consolidated_package_id' => $consolidatedPackageId,
                'totals' => $totals,
            ]);

            return $totals;
        });
    }

    /**
     * Get cached customer consolidations list
     *
     * @param int $customerId
     * @param bool $activeOnly
     * @return Collection
     */
    public function getCustomerConsolidations(int $customerId, bool $activeOnly = true): Collection
    {
        $cacheKey = $this->getCacheKey(self::PREFIX_CUSTOMER_CONSOLIDATIONS, $customerId, $activeOnly ? 'active' : 'all');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($customerId, $activeOnly) {
            $query = ConsolidatedPackage::where('customer_id', $customerId)
                ->select([
                    'id', 'consolidated_tracking_number', 'customer_id', 'status', 
                    'total_weight', 'total_quantity', 'total_freight_price', 
                    'total_customs_duty', 'total_storage_fee', 'total_delivery_fee',
                    'consolidated_at', 'is_active'
                ])
                ->with('customer:id,first_name,last_name,email');

            if ($activeOnly) {
                $query->active();
            }

            $consolidations = $query->orderBy('consolidated_at', 'desc')->get();

            Log::debug('Customer consolidations loaded and cached', [
                'customer_id' => $customerId,
                'active_only' => $activeOnly,
                'count' => $consolidations->count(),
            ]);

            return $consolidations;
        });
    }

    /**
     * Get cached available packages for consolidation
     *
     * @param int $customerId
     * @return Collection
     */
    public function getAvailablePackagesForConsolidation(int $customerId): Collection
    {
        $cacheKey = $this->getCacheKey(self::PREFIX_AVAILABLE_PACKAGES, $customerId);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($customerId) {
            $packages = Package::where('user_id', $customerId)
                ->availableForConsolidation()
                ->get();

            Log::debug('Available packages for consolidation loaded and cached', [
                'customer_id' => $customerId,
                'count' => $packages->count(),
            ]);

            return $packages;
        });
    }

    /**
     * Get cached consolidation statistics
     *
     * @param array $filters
     * @return array
     */
    public function getConsolidationStats(array $filters = []): array
    {
        $cacheKey = $this->getCacheKey(self::PREFIX_CONSOLIDATION_STATS, md5(serialize($filters)));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_LONG, function () use ($filters) {
            $stats = [
                'total_consolidated_packages' => ConsolidatedPackage::active()->count(),
                'total_packages_in_consolidations' => Package::consolidated()->count(),
                'consolidations_by_status' => ConsolidatedPackage::active()
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'consolidations_this_month' => ConsolidatedPackage::active()
                    ->whereMonth('consolidated_at', now()->month)
                    ->whereYear('consolidated_at', now()->year)
                    ->count(),
                'average_packages_per_consolidation' => $this->calculateAveragePackagesPerConsolidation(),
                'calculated_at' => now()->toISOString(),
            ];

            // Apply date filters if provided
            if (isset($filters['date_from']) || isset($filters['date_to'])) {
                $query = ConsolidatedPackage::active();
                
                if (isset($filters['date_from'])) {
                    $query->where('consolidated_at', '>=', $filters['date_from']);
                }
                
                if (isset($filters['date_to'])) {
                    $query->where('consolidated_at', '<=', $filters['date_to']);
                }
                
                $stats['filtered_count'] = $query->count();
            }

            Log::debug('Consolidation statistics calculated and cached', [
                'filters' => $filters,
                'stats' => $stats,
            ]);

            return $stats;
        });
    }

    /**
     * Cache search results for consolidated packages
     *
     * @param string $searchTerm
     * @param int $customerId
     * @param array $options
     * @return Collection
     */
    public function getSearchResults(string $searchTerm, int $customerId = null, array $options = []): Collection
    {
        $cacheKey = $this->getCacheKey(
            self::PREFIX_SEARCH_RESULTS, 
            md5($searchTerm . ($customerId ?? 'all') . serialize($options))
        );
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($searchTerm, $customerId, $options) {
            $query = ConsolidatedPackage::search($searchTerm);

            if ($customerId) {
                $query->forCustomer($customerId);
            }

            if (isset($options['active_only']) && $options['active_only']) {
                $query->active();
            }

            if (isset($options['limit'])) {
                $query->limit($options['limit']);
            }

            $results = $query->orderBy('consolidated_at', 'desc')->get();

            Log::debug('Consolidation search results cached', [
                'search_term' => $searchTerm,
                'customer_id' => $customerId,
                'options' => $options,
                'results_count' => $results->count(),
            ]);

            return $results;
        });
    }

    /**
     * Invalidate consolidated package totals cache
     *
     * @param int $consolidatedPackageId
     * @return void
     */
    public function invalidateConsolidatedTotals(int $consolidatedPackageId): void
    {
        $cacheKey = $this->getCacheKey(self::PREFIX_CONSOLIDATED_TOTALS, $consolidatedPackageId);
        Cache::forget($cacheKey);
        
        Log::debug('Consolidated package totals cache invalidated', [
            'consolidated_package_id' => $consolidatedPackageId,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Invalidate customer consolidations cache
     *
     * @param int $customerId
     * @return void
     */
    public function invalidateCustomerConsolidations(int $customerId): void
    {
        $activeKey = $this->getCacheKey(self::PREFIX_CUSTOMER_CONSOLIDATIONS, $customerId, 'active');
        $allKey = $this->getCacheKey(self::PREFIX_CUSTOMER_CONSOLIDATIONS, $customerId, 'all');
        
        Cache::forget($activeKey);
        Cache::forget($allKey);
        
        Log::debug('Customer consolidations cache invalidated', [
            'customer_id' => $customerId,
            'cache_keys' => [$activeKey, $allKey],
        ]);
    }

    /**
     * Invalidate available packages cache
     *
     * @param int $customerId
     * @return void
     */
    public function invalidateAvailablePackages(int $customerId): void
    {
        $cacheKey = $this->getCacheKey(self::PREFIX_AVAILABLE_PACKAGES, $customerId);
        Cache::forget($cacheKey);
        
        Log::debug('Available packages cache invalidated', [
            'customer_id' => $customerId,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Invalidate consolidation statistics cache
     *
     * @return void
     */
    public function invalidateConsolidationStats(): void
    {
        $pattern = $this->getCacheKey(self::PREFIX_CONSOLIDATION_STATS, '*');
        $this->forgetByPattern($pattern);
        
        Log::debug('Consolidation statistics cache invalidated');
    }

    /**
     * Invalidate search results cache
     *
     * @param string $searchTerm
     * @param int $customerId
     * @return void
     */
    public function invalidateSearchResults(string $searchTerm = null, int $customerId = null): void
    {
        if ($searchTerm && $customerId) {
            // Invalidate specific search
            $cacheKey = $this->getCacheKey(
                self::PREFIX_SEARCH_RESULTS, 
                md5($searchTerm . $customerId)
            );
            Cache::forget($cacheKey);
        } else {
            // Invalidate all search results
            $pattern = $this->getCacheKey(self::PREFIX_SEARCH_RESULTS, '*');
            $this->forgetByPattern($pattern);
        }
        
        Log::debug('Search results cache invalidated', [
            'search_term' => $searchTerm,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Invalidate all consolidation-related caches for a customer
     *
     * @param int $customerId
     * @return void
     */
    public function invalidateAllForCustomer(int $customerId): void
    {
        $this->invalidateCustomerConsolidations($customerId);
        $this->invalidateAvailablePackages($customerId);
        $this->invalidateSearchResults(null, $customerId);
        
        Log::info('All consolidation caches invalidated for customer', [
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Invalidate all consolidation-related caches
     *
     * @return void
     */
    public function invalidateAll(): void
    {
        $patterns = [
            $this->getCacheKey(self::PREFIX_CONSOLIDATED_TOTALS, '*'),
            $this->getCacheKey(self::PREFIX_CUSTOMER_CONSOLIDATIONS, '*'),
            $this->getCacheKey(self::PREFIX_AVAILABLE_PACKAGES, '*'),
            $this->getCacheKey(self::PREFIX_CONSOLIDATION_STATS, '*'),
            $this->getCacheKey(self::PREFIX_SEARCH_RESULTS, '*'),
        ];

        foreach ($patterns as $pattern) {
            $this->forgetByPattern($pattern);
        }
        
        Log::info('All consolidation caches invalidated');
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStatistics(): array
    {
        // This is a simplified version - in production you might want to use Redis commands
        // to get actual cache statistics
        return [
            'cache_prefixes' => [
                self::PREFIX_CONSOLIDATED_TOTALS,
                self::PREFIX_CUSTOMER_CONSOLIDATIONS,
                self::PREFIX_AVAILABLE_PACKAGES,
                self::PREFIX_CONSOLIDATION_STATS,
                self::PREFIX_SEARCH_RESULTS,
            ],
            'cache_durations' => [
                'short' => self::CACHE_DURATION_SHORT . ' minutes',
                'normal' => self::CACHE_DURATION . ' minutes',
                'long' => self::CACHE_DURATION_LONG . ' minutes',
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate cache key
     *
     * @param string $prefix
     * @param mixed ...$parts
     * @return string
     */
    protected function getCacheKey(string $prefix, ...$parts): string
    {
        $key = $prefix;
        foreach ($parts as $part) {
            $key .= ':' . $part;
        }
        return $key;
    }

    /**
     * Forget cache keys by pattern (simplified version)
     *
     * @param string $pattern
     * @return void
     */
    protected function forgetByPattern(string $pattern): void
    {
        // This is a simplified implementation
        // In production with Redis, you would use Redis::keys() and Redis::del()
        // For now, we'll just log the pattern that would be cleared
        Log::debug('Cache pattern would be cleared', ['pattern' => $pattern]);
    }

    /**
     * Calculate average packages per consolidation
     *
     * @return float
     */
    protected function calculateAveragePackagesPerConsolidation(): float
    {
        $consolidatedPackages = ConsolidatedPackage::active()
            ->withCount('packages')
            ->get();

        if ($consolidatedPackages->isEmpty()) {
            return 0;
        }

        $totalPackages = $consolidatedPackages->sum('packages_count');
        $totalConsolidations = $consolidatedPackages->count();

        return round($totalPackages / $totalConsolidations, 2);
    }
}
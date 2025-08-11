<?php

namespace App\Services;

use App\Models\User;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerStatisticsService
{
    /**
     * Cache duration in seconds (1 hour)
     */
    const CACHE_DURATION = 3600;
    
    /**
     * Cache duration for financial summary (30 minutes)
     */
    const FINANCIAL_CACHE_DURATION = 1800;
    
    /**
     * Cache duration for package metrics (45 minutes)
     */
    const PACKAGE_CACHE_DURATION = 2700;
    
    /**
     * Cache duration for shipping patterns (2 hours)
     */
    const PATTERNS_CACHE_DURATION = 7200;
    
    /**
     * Cache tag for customer statistics
     */
    const CACHE_TAG_CUSTOMER_STATS = 'customer_stats';
    
    /**
     * Cache tag for customer financial data
     */
    const CACHE_TAG_CUSTOMER_FINANCIAL = 'customer_financial';
    
    /**
     * Cache tag for customer packages
     */
    const CACHE_TAG_CUSTOMER_PACKAGES = 'customer_packages';

    /**
     * Get comprehensive customer statistics with caching
     *
     * @param User $customer
     * @param bool $forceRefresh
     * @return array
     */
    public function getCustomerStatistics(User $customer, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey('stats', $customer->id);
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($customer) {
            Log::debug("Calculating customer statistics for customer {$customer->id}");
            return $this->calculateCustomerStatistics($customer);
        });
    }

    /**
     * Get customer financial summary with caching
     *
     * @param User $customer
     * @param bool $forceRefresh
     * @return array
     */
    public function getFinancialSummary(User $customer, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey('financial', $customer->id);
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, self::FINANCIAL_CACHE_DURATION, function () use ($customer) {
            Log::debug("Calculating financial summary for customer {$customer->id}");
            return $this->calculateFinancialSummary($customer);
        });
    }

    /**
     * Get shipping patterns and frequency analysis
     *
     * @param User $customer
     * @param bool $forceRefresh
     * @return array
     */
    public function getShippingPatterns(User $customer, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey('patterns', $customer->id);
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, self::PATTERNS_CACHE_DURATION, function () use ($customer) {
            Log::debug("Analyzing shipping patterns for customer {$customer->id}");
            return $this->analyzeShippingPatterns($customer);
        });
    }

    /**
     * Get package count and value calculations
     *
     * @param User $customer
     * @param bool $forceRefresh
     * @return array
     */
    public function getPackageMetrics(User $customer, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey('packages', $customer->id);
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, self::PACKAGE_CACHE_DURATION, function () use ($customer) {
            Log::debug("Calculating package metrics for customer {$customer->id}");
            return $this->calculatePackageMetrics($customer);
        });
    }

    /**
     * Calculate comprehensive customer statistics
     *
     * @param User $customer
     * @return array
     */
    private function calculateCustomerStatistics(User $customer): array
    {
        $packageMetrics = $this->calculatePackageMetrics($customer);
        $financialSummary = $this->calculateFinancialSummary($customer);
        $shippingPatterns = $this->analyzeShippingPatterns($customer);

        return [
            'customer_id' => $customer->id,
            'packages' => $packageMetrics,
            'financial' => $financialSummary,
            'patterns' => $shippingPatterns,
            'generated_at' => Carbon::now(),
        ];
    }

    /**
     * Calculate package metrics and statistics
     *
     * @param User $customer
     * @return array
     */
    private function calculatePackageMetrics(User $customer): array
    {
        $packageStats = $customer->packages()
            ->selectRaw('
                COUNT(*) as total_packages,
                COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered_packages,
                COUNT(CASE WHEN status = "shipped" THEN 1 END) as shipped_packages,
                COUNT(CASE WHEN status = "customs" THEN 1 END) as customs_packages,
                COUNT(CASE WHEN status = "ready" THEN 1 END) as ready_packages,
                COUNT(CASE WHEN status = "delayed" THEN 1 END) as delayed_packages,
                COUNT(CASE WHEN status = "processing" THEN 1 END) as processing_packages,
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_packages,
                COALESCE(AVG(weight), 0) as avg_weight,
                COALESCE(SUM(weight), 0) as total_weight,
                COALESCE(MAX(weight), 0) as max_weight,
                COALESCE(MIN(weight), 0) as min_weight,
                COALESCE(AVG(cubic_feet), 0) as avg_cubic_feet,
                COALESCE(SUM(cubic_feet), 0) as total_cubic_feet
            ')
            ->first();

        // Calculate delivery rate
        $totalPackages = $packageStats->total_packages ?? 0;
        $deliveredPackages = $packageStats->delivered_packages ?? 0;
        $deliveryRate = $totalPackages > 0 ? ($deliveredPackages / $totalPackages) * 100 : 0;

        return [
            'total_packages' => $totalPackages,
            'status_breakdown' => [
                'delivered' => $deliveredPackages,
                'in_transit' => ($packageStats->shipped_packages ?? 0) + ($packageStats->customs_packages ?? 0),
                'ready_for_pickup' => $packageStats->ready_packages ?? 0,
                'delayed' => $packageStats->delayed_packages ?? 0,
                'processing' => $packageStats->processing_packages ?? 0,
                'pending' => $packageStats->pending_packages ?? 0,
            ],
            'shipping_frequency' => $this->calculateShippingFrequency($customer, $totalPackages),
            'weight_statistics' => [
                'total_weight' => round($packageStats->total_weight ?? 0, 2),
                'average_weight' => round($packageStats->avg_weight ?? 0, 2),
                'max_weight' => round($packageStats->max_weight ?? 0, 2),
                'min_weight' => round($packageStats->min_weight ?? 0, 2),
            ],
            'volume_statistics' => [
                'total_cubic_feet' => round($packageStats->total_cubic_feet ?? 0, 3),
                'average_cubic_feet' => round($packageStats->avg_cubic_feet ?? 0, 3),
            ],
            'delivery_rate' => round($deliveryRate, 2),
        ];
    }

    /**
     * Calculate financial summary and breakdowns
     *
     * @param User $customer
     * @return array
     */
    private function calculateFinancialSummary(User $customer): array
    {
        $financialStats = $customer->packages()
            ->selectRaw('
                COUNT(*) as total_packages,
                COALESCE(SUM(freight_price), 0) as total_freight,
                COALESCE(SUM(customs_duty), 0) as total_customs,
                COALESCE(SUM(storage_fee), 0) as total_storage,
                COALESCE(SUM(delivery_fee), 0) as total_delivery,
                COALESCE(AVG(freight_price), 0) as avg_freight,
                COALESCE(AVG(customs_duty), 0) as avg_customs,
                COALESCE(AVG(storage_fee), 0) as avg_storage,
                COALESCE(AVG(delivery_fee), 0) as avg_delivery,
                COALESCE(MAX(freight_price + customs_duty + storage_fee + delivery_fee), 0) as highest_package_cost,
                COALESCE(MIN(freight_price + customs_duty + storage_fee + delivery_fee), 0) as lowest_package_cost
            ')
            ->first();

        $totalSpent = ($financialStats->total_freight ?? 0) + 
                     ($financialStats->total_customs ?? 0) + 
                     ($financialStats->total_storage ?? 0) + 
                     ($financialStats->total_delivery ?? 0);

        $totalPackages = $financialStats->total_packages ?? 0;
        $averagePerPackage = $totalPackages > 0 ? $totalSpent / $totalPackages : 0;

        // Calculate cost distribution percentages
        $freightPercentage = $totalSpent > 0 ? (($financialStats->total_freight ?? 0) / $totalSpent) * 100 : 0;
        $customsPercentage = $totalSpent > 0 ? (($financialStats->total_customs ?? 0) / $totalSpent) * 100 : 0;
        $storagePercentage = $totalSpent > 0 ? (($financialStats->total_storage ?? 0) / $totalSpent) * 100 : 0;
        $deliveryPercentage = $totalSpent > 0 ? (($financialStats->total_delivery ?? 0) / $totalSpent) * 100 : 0;

        return [
            'total_spent' => round($totalSpent, 2),
            'average_per_package' => round($averagePerPackage, 2),
            'cost_breakdown' => [
                'freight' => round($financialStats->total_freight ?? 0, 2),
                'customs' => round($financialStats->total_customs ?? 0, 2),
                'storage' => round($financialStats->total_storage ?? 0, 2),
                'delivery' => round($financialStats->total_delivery ?? 0, 2),
            ],
            'cost_percentages' => [
                'freight' => round($freightPercentage, 1),
                'customs' => round($customsPercentage, 1),
                'storage' => round($storagePercentage, 1),
                'delivery' => round($deliveryPercentage, 1),
            ],
            'average_costs' => [
                'freight' => round($financialStats->avg_freight ?? 0, 2),
                'customs' => round($financialStats->avg_customs ?? 0, 2),
                'storage' => round($financialStats->avg_storage ?? 0, 2),
                'delivery' => round($financialStats->avg_delivery ?? 0, 2),
            ],
            'cost_range' => [
                'highest_package' => round($financialStats->highest_package_cost ?? 0, 2),
                'lowest_package' => round($financialStats->lowest_package_cost ?? 0, 2),
            ],
        ];
    }

    /**
     * Analyze shipping patterns and frequency
     *
     * @param User $customer
     * @return array
     */
    private function analyzeShippingPatterns(User $customer): array
    {
        // Get first and last package dates
        $firstPackage = $customer->packages()->oldest('created_at')->first();
        $lastPackage = $customer->packages()->latest('created_at')->first();
        
        if (!$firstPackage) {
            return [
                'shipping_frequency' => 0,
                'months_active' => 0,
                'first_shipment' => null,
                'last_shipment' => null,
                'monthly_breakdown' => [],
                'seasonal_patterns' => [],
                'average_days_between_shipments' => 0,
            ];
        }

        $firstDate = Carbon::parse($firstPackage->created_at);
        $lastDate = Carbon::parse($lastPackage->created_at);
        $monthsActive = max(1, $firstDate->diffInMonths($lastDate) + 1);
        $totalPackages = $customer->packages()->count();
        
        // Calculate shipping frequency (packages per month)
        $shippingFrequency = $totalPackages / $monthsActive;

        // Get monthly breakdown for the last 12 months
        $monthlyBreakdown = $this->getMonthlyBreakdown($customer);

        // Analyze seasonal patterns
        $seasonalPatterns = $this->analyzeSeasonalPatterns($customer);

        // Calculate average days between shipments
        $averageDaysBetween = $this->calculateAverageDaysBetweenShipments($customer);

        return [
            'shipping_frequency' => round($shippingFrequency, 2),
            'months_active' => $monthsActive,
            'first_shipment' => $firstDate->toDateString(),
            'last_shipment' => $lastDate->toDateString(),
            'monthly_breakdown' => $monthlyBreakdown,
            'seasonal_patterns' => $seasonalPatterns,
            'average_days_between_shipments' => $averageDaysBetween,
        ];
    }

    /**
     * Get monthly package breakdown for the last 12 months
     *
     * @param User $customer
     * @return array
     */
    private function getMonthlyBreakdown(User $customer): array
    {
        // Use database-agnostic date functions
        $yearFunction = $this->getYearFunction();
        $monthFunction = $this->getMonthFunction();
        
        $monthlyData = $customer->packages()
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->selectRaw("
                {$yearFunction} as year,
                {$monthFunction} as month,
                COUNT(*) as package_count,
                COALESCE(SUM(freight_price + customs_duty + storage_fee + delivery_fee), 0) as total_spent
            ")
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $breakdown = [];
        foreach ($monthlyData as $data) {
            $monthName = Carbon::createFromDate($data->year, $data->month, 1)->format('M Y');
            $breakdown[] = [
                'month' => $monthName,
                'year' => (int) $data->year,
                'month_number' => (int) $data->month,
                'package_count' => $data->package_count,
                'total_spent' => round($data->total_spent, 2),
            ];
        }

        return $breakdown;
    }

    /**
     * Analyze seasonal shipping patterns
     *
     * @param User $customer
     * @return array
     */
    private function analyzeSeasonalPatterns(User $customer): array
    {
        $monthFunction = $this->getMonthFunction();
        
        $seasonalData = $customer->packages()
            ->selectRaw("
                CASE 
                    WHEN {$monthFunction} IN (12, 1, 2) THEN 'Winter'
                    WHEN {$monthFunction} IN (3, 4, 5) THEN 'Spring'
                    WHEN {$monthFunction} IN (6, 7, 8) THEN 'Summer'
                    WHEN {$monthFunction} IN (9, 10, 11) THEN 'Fall'
                END as season,
                COUNT(*) as package_count,
                COALESCE(AVG(freight_price + customs_duty + storage_fee + delivery_fee), 0) as avg_cost
            ")
            ->groupBy('season')
            ->get();

        $patterns = [];
        foreach ($seasonalData as $data) {
            if ($data->season) {
                $patterns[$data->season] = [
                    'package_count' => $data->package_count,
                    'average_cost' => round($data->avg_cost, 2),
                ];
            }
        }

        return $patterns;
    }

    /**
     * Calculate shipping frequency (packages per month)
     *
     * @param User $customer
     * @param int $totalPackages
     * @return float
     */
    private function calculateShippingFrequency(User $customer, int $totalPackages): float
    {
        if ($totalPackages === 0) {
            return 0;
        }

        $firstPackage = $customer->packages()->orderBy('created_at')->first();
        if (!$firstPackage) {
            return 0;
        }

        $monthsSinceFirst = Carbon::parse($firstPackage->created_at)->diffInMonths(Carbon::now());
        if ($monthsSinceFirst === 0) {
            $monthsSinceFirst = 1; // At least 1 month to avoid division by zero
        }

        return round($totalPackages / $monthsSinceFirst, 1);
    }

    /**
     * Calculate average days between shipments
     *
     * @param User $customer
     * @return int
     */
    private function calculateAverageDaysBetweenShipments(User $customer): int
    {
        $packageDates = $customer->packages()
            ->orderBy('created_at')
            ->pluck('created_at')
            ->map(function ($date) {
                return Carbon::parse($date);
            });

        if ($packageDates->count() < 2) {
            return 0;
        }

        $totalDays = 0;
        $intervals = 0;

        for ($i = 1; $i < $packageDates->count(); $i++) {
            $daysDiff = $packageDates[$i]->diffInDays($packageDates[$i - 1]);
            $totalDays += $daysDiff;
            $intervals++;
        }

        return $intervals > 0 ? round($totalDays / $intervals) : 0;
    }

    /**
     * Generate cache key for customer data
     *
     * @param string $type
     * @param int $customerId
     * @return string
     */
    private function getCacheKey(string $type, int $customerId): string
    {
        return "customer_{$type}_{$customerId}";
    }

    /**
     * Clear cache for a specific customer
     *
     * @param User $customer
     * @return void
     */
    public function clearCustomerCache(User $customer): void
    {
        $cacheKeys = [
            $this->getCacheKey('stats', $customer->id),
            $this->getCacheKey('financial', $customer->id),
            $this->getCacheKey('patterns', $customer->id),
            $this->getCacheKey('packages', $customer->id),
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
            Log::debug("Cleared cache key: {$key}");
        }
        
        Log::debug("Cleared all cache for customer {$customer->id}");
    }

    /**
     * Clear specific cache type for a customer
     *
     * @param User $customer
     * @param string $type
     * @return void
     */
    public function clearCustomerCacheType(User $customer, string $type): void
    {
        $cacheKey = $this->getCacheKey($type, $customer->id);
        Cache::forget($cacheKey);
        Log::debug("Cleared {$type} cache for customer {$customer->id}");
    }

    /**
     * Clear cache for multiple customers
     *
     * @param array $customerIds
     * @return void
     */
    public function clearMultipleCustomersCache(array $customerIds): void
    {
        $types = ['stats', 'financial', 'patterns', 'packages'];
        $clearedCount = 0;
        
        foreach ($customerIds as $customerId) {
            foreach ($types as $type) {
                $cacheKey = $this->getCacheKey($type, $customerId);
                Cache::forget($cacheKey);
                $clearedCount++;
            }
        }
        
        Log::info("Cleared cache for {$clearedCount} keys across " . count($customerIds) . " customers");
    }

    /**
     * Clear all customer statistics cache
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        // Get all customer IDs to clear their specific caches
        $customerIds = User::customers()->pluck('id')->toArray();
        
        if (!empty($customerIds)) {
            $this->clearMultipleCustomersCache($customerIds);
        }
        
        Log::info("Cleared all customer statistics cache");
    }

    /**
     * Get cache status for a customer
     *
     * @param User $customer
     * @return array
     */
    public function getCacheStatus(User $customer): array
    {
        $cacheKeys = [
            'stats' => $this->getCacheKey('stats', $customer->id),
            'financial' => $this->getCacheKey('financial', $customer->id),
            'patterns' => $this->getCacheKey('patterns', $customer->id),
            'packages' => $this->getCacheKey('packages', $customer->id),
        ];

        $status = [];
        foreach ($cacheKeys as $type => $key) {
            $status[$type] = [
                'cached' => Cache::has($key),
                'key' => $key,
                'ttl' => $this->getCacheTtl($key),
            ];
        }

        return $status;
    }

    /**
     * Get cache TTL for a key
     *
     * @param string $key
     * @return int|null
     */
    private function getCacheTtl(string $key): ?int
    {
        try {
            // This is implementation-specific and may not work with all cache drivers
            $store = Cache::getStore();
            
            // Check if it's a Redis store
            if (method_exists($store, 'getRedis')) {
                return $store->getRedis()->ttl($key);
            }
            
            // For other stores, return null
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Warm up cache for a customer
     *
     * @param User $customer
     * @return void
     */
    public function warmUpCustomerCache(User $customer): void
    {
        Log::debug("Warming up cache for customer {$customer->id}");
        
        // Pre-load all customer statistics
        $this->getCustomerStatistics($customer);
        $this->getFinancialSummary($customer);
        $this->getShippingPatterns($customer);
        $this->getPackageMetrics($customer);
        
        Log::debug("Cache warmed up for customer {$customer->id}");
    }

    /**
     * Warm up cache for multiple customers
     *
     * @param array $customerIds
     * @return void
     */
    public function warmUpMultipleCustomersCache(array $customerIds): void
    {
        Log::info("Warming up cache for " . count($customerIds) . " customers");
        
        $customers = User::whereIn('id', $customerIds)->get();
        
        foreach ($customers as $customer) {
            try {
                $this->warmUpCustomerCache($customer);
            } catch (\Exception $e) {
                Log::error("Failed to warm up cache for customer {$customer->id}: " . $e->getMessage());
            }
        }
        
        Log::info("Cache warm-up completed for " . count($customerIds) . " customers");
    }

    /**
     * Get database-specific YEAR function
     *
     * @return string
     */
    private function getYearFunction(): string
    {
        $driver = DB::connection()->getDriverName();
        
        switch ($driver) {
            case 'sqlite':
                return 'strftime("%Y", created_at)';
            case 'mysql':
            case 'mariadb':
            default:
                return 'YEAR(created_at)';
        }
    }

    /**
     * Get database-specific MONTH function
     *
     * @return string
     */
    private function getMonthFunction(): string
    {
        $driver = DB::connection()->getDriverName();
        
        switch ($driver) {
            case 'sqlite':
                return 'strftime("%m", created_at)';
            case 'mysql':
            case 'mariadb':
            default:
                return 'MONTH(created_at)';
        }
    }

    /**
     * Get cache performance metrics
     *
     * @return array
     */
    public function getCachePerformanceMetrics(): array
    {
        $customerIds = User::customers()->pluck('id')->toArray();
        $totalCustomers = count($customerIds);
        $cachedCustomers = 0;
        $cacheTypes = ['stats', 'financial', 'patterns', 'packages'];
        $typeCounts = array_fill_keys($cacheTypes, 0);
        
        foreach ($customerIds as $customerId) {
            $customerCached = false;
            foreach ($cacheTypes as $type) {
                $cacheKey = $this->getCacheKey($type, $customerId);
                if (Cache::has($cacheKey)) {
                    $typeCounts[$type]++;
                    $customerCached = true;
                }
            }
            if ($customerCached) {
                $cachedCustomers++;
            }
        }
        
        return [
            'total_customers' => $totalCustomers,
            'cached_customers' => $cachedCustomers,
            'cache_coverage_percentage' => $totalCustomers > 0 ? round(($cachedCustomers / $totalCustomers) * 100, 2) : 0,
            'cache_by_type' => $typeCounts,
            'type_coverage' => array_map(function($count) use ($totalCustomers) {
                return $totalCustomers > 0 ? round(($count / $totalCustomers) * 100, 2) : 0;
            }, $typeCounts),
        ];
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportQueryOptimizationService
{
    protected $cachePrefix = 'report_query_cache';
    protected $slowQueryThreshold = 1000; // milliseconds
    protected $queryLogEnabled = true;

    /**
     * Execute optimized query with caching and monitoring
     */
    public function executeOptimizedQuery(string $cacheKey, callable $queryCallback, int $ttl = 900)
    {
        // Check cache first
        if (Cache::has($cacheKey)) {
            $this->logQueryPerformance($cacheKey, 0, true);
            return Cache::get($cacheKey);
        }

        // Execute query with timing
        $startTime = microtime(true);
        
        try {
            $result = $queryCallback();
            $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            // Cache the result
            Cache::put($cacheKey, $result, $ttl);
            
            // Log performance metrics
            $this->logQueryPerformance($cacheKey, $executionTime, false);
            
            return $result;
            
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            $this->logQueryError($cacheKey, $executionTime, $e);
            throw $e;
        }
    }

    /**
     * Get optimized sales collections query
     */
    public function getOptimizedSalesCollectionsQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now();
        $manifestIds = $filters['manifest_ids'] ?? null;
        $officeIds = $filters['office_ids'] ?? null;

        $query = DB::table('manifests as m')
            ->select([
                'm.id as manifest_id',
                'm.name as manifest_number',
                'm.type as manifest_type',
                'm.shipment_date as created_at',
                'o.name as office_name',
                DB::raw('COUNT(p.id) as total_packages'),
                DB::raw('SUM(COALESCE(p.freight_price, 0) + COALESCE(p.clearance_fee, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)) as total_owed'),
                DB::raw('SUM(CASE WHEN p.status = "delivered" THEN 1 ELSE 0 END) as delivered_count')
            ])
            ->leftJoin('packages as p', 'm.id', '=', 'p.manifest_id')
            ->leftJoin('offices as o', 'p.office_id', '=', 'o.id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        // Apply filters with proper indexing
        if ($manifestIds) {
            $query->whereIn('m.id', $manifestIds);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        $query->groupBy('m.id', 'm.name', 'm.type', 'm.shipment_date', 'o.name')
              ->orderBy('m.shipment_date', 'desc');

        return $query;
    }

    /**
     * Get optimized manifest metrics query
     */
    public function getOptimizedManifestMetricsQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now();
        $manifestType = $filters['manifest_type'] ?? null;
        $officeIds = $filters['office_ids'] ?? null;

        $query = DB::table('manifests as m')
            ->select([
                'm.id as manifest_id',
                'm.name as manifest_number',
                'm.type as manifest_type',
                'm.shipment_date as created_at',
                'm.created_at as manifest_created_at',
                'o.name as office_name',
                DB::raw('COUNT(p.id) as package_count'),
                DB::raw('SUM(COALESCE(p.weight, 0)) as total_weight'),
                DB::raw('SUM(COALESCE(p.cubic_feet, 0)) as total_volume_stored'),
                DB::raw('SUM(CASE WHEN p.status = "delivered" THEN 1 ELSE 0 END) as delivered_count'),
                DB::raw('SUM(CASE WHEN p.status IN ("shipped", "customs") THEN 1 ELSE 0 END) as in_transit_count'),
                DB::raw('SUM(CASE WHEN p.status = "pending" THEN 1 ELSE 0 END) as pending_count')
            ])
            ->leftJoin('packages as p', 'm.id', '=', 'p.manifest_id')
            ->leftJoin('offices as o', 'p.office_id', '=', 'o.id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestType) {
            $query->where('m.type', $manifestType);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        $query->groupBy('m.id', 'm.name', 'm.type', 'm.shipment_date', 'm.created_at', 'o.name')
              ->orderBy('m.shipment_date', 'desc');

        return $query;
    }

    /**
     * Get optimized customer statistics query
     */
    public function getOptimizedCustomerStatsQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now();
        $customerIds = $filters['customer_ids'] ?? null;

        $query = DB::table('users as u')
            ->select([
                'u.id as customer_id',
                DB::raw('CONCAT(u.first_name, " ", u.last_name) as customer_name'),
                'u.email',
                'u.account_balance',
                'u.updated_at as last_activity',
                DB::raw('COUNT(p.id) as total_packages'),
                DB::raw('SUM(COALESCE(p.freight_price, 0) + COALESCE(p.clearance_fee, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)) as total_spent'),
                DB::raw('SUM(CASE WHEN p.status = "delivered" THEN 1 ELSE 0 END) as delivered_packages'),
                DB::raw('MAX(p.created_at) as last_package_date')
            ])
            ->leftJoin('packages as p', function($join) use ($dateFrom, $dateTo) {
                $join->on('u.id', '=', 'p.user_id')
                     ->whereBetween('p.created_at', [$dateFrom, $dateTo]);
            })
            ->whereExists(function($query) use ($dateFrom, $dateTo) {
                $query->select(DB::raw(1))
                      ->from('packages')
                      ->whereRaw('packages.user_id = u.id')
                      ->whereBetween('created_at', [$dateFrom, $dateTo]);
            });

        if ($customerIds) {
            $query->whereIn('u.id', $customerIds);
        }

        $query->groupBy('u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.account_balance', 'u.updated_at')
              ->orderBy('total_spent', 'desc');

        return $query;
    }

    /**
     * Get collected amounts by manifest with optimized query
     */
    public function getCollectedAmountsByManifest(array $manifestIds): array
    {
        if (empty($manifestIds)) {
            return [];
        }

        $cacheKey = $this->generateCacheKey('collected_amounts', ['manifest_ids' => $manifestIds]);
        
        return $this->executeOptimizedQuery($cacheKey, function() use ($manifestIds) {
            $collected = DB::table('package_distributions as pd')
                ->select([
                    'p.manifest_id',
                    DB::raw('SUM(pd.total_amount) as total_collected')
                ])
                ->join('package_distribution_items as pdi', 'pd.id', '=', 'pdi.distribution_id')
                ->join('packages as p', 'pdi.package_id', '=', 'p.id')
                ->whereIn('p.manifest_id', $manifestIds)
                ->groupBy('p.manifest_id')
                ->get();

            return $collected->pluck('total_collected', 'manifest_id')->toArray();
        }, 1800); // 30 minutes cache for collected amounts
    }

    /**
     * Get financial breakdown with optimized aggregation
     */
    public function getOptimizedFinancialBreakdown(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now();

        $cacheKey = $this->generateCacheKey('financial_breakdown', $filters);
        
        return $this->executeOptimizedQuery($cacheKey, function() use ($dateFrom, $dateTo) {
            // Single query for revenue breakdown
            $revenueBreakdown = DB::table('packages')
                ->selectRaw('
                    SUM(COALESCE(freight_price, 0)) as freight_revenue,
                    SUM(COALESCE(clearance_fee, 0)) as clearance_revenue,
                    SUM(COALESCE(storage_fee, 0)) as storage_revenue,
                    SUM(COALESCE(delivery_fee, 0)) as delivery_revenue,
                    COUNT(*) as package_count
                ')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->first();

            // Single query for collections
            $collections = DB::table('customer_transactions')
                ->selectRaw('
                    SUM(amount) as total_collected,
                    COUNT(*) as payment_count,
                    AVG(amount) as average_payment
                ')
                ->where('type', 'payment')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->first();

            // Single query for outstanding balances
            $outstanding = DB::table('users')
                ->selectRaw('
                    SUM(ABS(account_balance)) as total_outstanding,
                    COUNT(*) as customers_with_debt
                ')
                ->where('account_balance', '<', 0)
                ->first();

            return [
                'revenue_breakdown' => [
                    'freight_revenue' => (float) ($revenueBreakdown->freight_revenue ?? 0),
                    'clearance_revenue' => (float) ($revenueBreakdown->clearance_revenue ?? 0),
                    'storage_revenue' => (float) ($revenueBreakdown->storage_revenue ?? 0),
                    'delivery_revenue' => (float) ($revenueBreakdown->delivery_revenue ?? 0),
                    'total_revenue' => (float) (
                        ($revenueBreakdown->freight_revenue ?? 0) + 
                        ($revenueBreakdown->clearance_revenue ?? 0) + 
                        ($revenueBreakdown->storage_revenue ?? 0) + 
                        ($revenueBreakdown->delivery_revenue ?? 0)
                    ),
                    'package_count' => $revenueBreakdown->package_count ?? 0
                ],
                'collections' => [
                    'total_collected' => (float) ($collections->total_collected ?? 0),
                    'payment_count' => $collections->payment_count ?? 0,
                    'average_payment' => (float) ($collections->average_payment ?? 0)
                ],
                'outstanding' => [
                    'total_outstanding' => (float) ($outstanding->total_outstanding ?? 0),
                    'customers_with_debt' => $outstanding->customers_with_debt ?? 0
                ]
            ];
        }, 900); // 15 minutes cache
    }

    /**
     * Analyze query performance and suggest optimizations
     */
    public function analyzeQueryPerformance(): array
    {
        $performanceData = Cache::get('query_performance_log', []);
        
        if (empty($performanceData)) {
            return [
                'total_queries' => 0,
                'slow_queries' => 0,
                'cache_hit_rate' => 0,
                'average_execution_time' => 0,
                'slow_query_percentage' => 0,
                'recommendations' => []
            ];
        }

        $totalQueries = count($performanceData);
        $slowQueries = collect($performanceData)->where('execution_time', '>', $this->slowQueryThreshold)->count();
        $cacheHits = collect($performanceData)->where('from_cache', true)->count();
        $avgExecutionTime = collect($performanceData)->avg('execution_time');

        $recommendations = $this->generateOptimizationRecommendations($performanceData);

        return [
            'total_queries' => $totalQueries,
            'slow_queries' => $slowQueries,
            'cache_hit_rate' => $totalQueries > 0 ? round(($cacheHits / $totalQueries) * 100, 2) : 0,
            'average_execution_time' => round($avgExecutionTime, 2),
            'slow_query_percentage' => $totalQueries > 0 ? round(($slowQueries / $totalQueries) * 100, 2) : 0,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Generate optimization recommendations based on performance data
     */
    protected function generateOptimizationRecommendations(array $performanceData): array
    {
        $recommendations = [];
        $slowQueries = collect($performanceData)->where('execution_time', '>', $this->slowQueryThreshold);
        
        if ($slowQueries->count() > 0) {
            $recommendations[] = [
                'type' => 'slow_queries',
                'message' => "Found {$slowQueries->count()} slow queries. Consider adding more specific indexes.",
                'priority' => 'high'
            ];
        }

        $cacheHitRate = collect($performanceData)->where('from_cache', true)->count() / count($performanceData) * 100;
        if ($cacheHitRate < 50) {
            $recommendations[] = [
                'type' => 'cache_optimization',
                'message' => "Cache hit rate is {$cacheHitRate}%. Consider increasing cache TTL for stable data.",
                'priority' => 'medium'
            ];
        }

        $frequentQueries = collect($performanceData)->groupBy('cache_key')->map->count()->sortDesc()->take(5);
        if ($frequentQueries->max() > 10) {
            $recommendations[] = [
                'type' => 'frequent_queries',
                'message' => "Some queries are executed very frequently. Consider pre-warming cache for these queries.",
                'priority' => 'medium'
            ];
        }

        return $recommendations;
    }

    /**
     * Log query performance metrics
     */
    protected function logQueryPerformance(string $cacheKey, float $executionTime, bool $fromCache): void
    {
        if (!$this->queryLogEnabled) {
            return;
        }

        $performanceLog = Cache::get('query_performance_log', []);
        
        $performanceLog[] = [
            'cache_key' => $cacheKey,
            'execution_time' => $executionTime,
            'from_cache' => $fromCache,
            'timestamp' => now(),
            'is_slow' => $executionTime > $this->slowQueryThreshold
        ];

        // Keep only last 1000 entries
        if (count($performanceLog) > 1000) {
            $performanceLog = array_slice($performanceLog, -1000);
        }

        Cache::put('query_performance_log', $performanceLog, 3600); // 1 hour

        // Log slow queries for debugging
        if ($executionTime > $this->slowQueryThreshold && !$fromCache) {
            Log::warning('Slow report query detected', [
                'cache_key' => $cacheKey,
                'execution_time' => $executionTime,
                'threshold' => $this->slowQueryThreshold
            ]);
        }
    }

    /**
     * Log query errors
     */
    protected function logQueryError(string $cacheKey, float $executionTime, \Exception $e): void
    {
        Log::error('Report query error', [
            'cache_key' => $cacheKey,
            'execution_time' => $executionTime,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Generate cache key for queries
     */
    protected function generateCacheKey(string $type, array $params): string
    {
        $paramHash = md5(serialize($params));
        return "{$this->cachePrefix}:{$type}:{$paramHash}";
    }

    /**
     * Clear query performance log
     */
    public function clearPerformanceLog(): void
    {
        Cache::forget('query_performance_log');
    }

    /**
     * Get query performance statistics
     */
    public function getPerformanceStatistics(): array
    {
        return $this->analyzeQueryPerformance();
    }

    /**
     * Enable or disable query logging
     */
    public function setQueryLogging(bool $enabled): void
    {
        $this->queryLogEnabled = $enabled;
    }

    /**
     * Set slow query threshold
     */
    public function setSlowQueryThreshold(int $milliseconds): void
    {
        $this->slowQueryThreshold = $milliseconds;
    }
}
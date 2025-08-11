<?php

namespace App\Services;

use App\Models\User;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CustomerQueryOptimizationService
{
    /**
     * Cache duration for query results (15 minutes)
     */
    const QUERY_CACHE_DURATION = 900;

    /**
     * Get optimized customer list with pagination
     *
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getOptimizedCustomerList(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey('customer_list', $filters, $perPage, $page);
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_DURATION, function () use ($filters, $perPage, $page) {
            Log::info("Executing optimized customer list query", ['filters' => $filters, 'page' => $page]);
            
            $query = User::query()
                ->forCustomerTable()
                ->when(isset($filters['status']), function ($query) use ($filters) {
                    return $query->byStatus($filters['status']);
                }, function ($query) {
                    return $query->activeCustomers();
                })
                ->when(isset($filters['search']), function ($query) use ($filters) {
                    return $query->search($filters['search']);
                })
                ->when(isset($filters['parish']), function ($query) use ($filters) {
                    return $query->whereHas('profile', function ($q) use ($filters) {
                        $q->where('parish', $filters['parish']);
                    });
                })
                ->when(isset($filters['registration_date_from']), function ($query) use ($filters) {
                    return $query->whereDate('created_at', '>=', $filters['registration_date_from']);
                })
                ->when(isset($filters['registration_date_to']), function ($query) use ($filters) {
                    return $query->whereDate('created_at', '<=', $filters['registration_date_to']);
                })
                ->orderBy('id', 'desc');

            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * Get optimized customer search results
     *
     * @param string $searchTerm
     * @param array $filters
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptimizedCustomerSearch(string $searchTerm, array $filters = [], int $limit = 20)
    {
        $cacheKey = $this->generateCacheKey('customer_search', array_merge($filters, ['term' => $searchTerm]), $limit);
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_DURATION, function () use ($searchTerm, $filters, $limit) {
            Log::info("Executing optimized customer search", ['term' => $searchTerm, 'filters' => $filters]);
            
            return User::query()
                ->forCustomerSearch($searchTerm)
                ->when(isset($filters['status']), function ($query) use ($filters) {
                    return $query->byStatus($filters['status']);
                }, function ($query) {
                    return $query->activeCustomers();
                })
                ->when(isset($filters['parish']), function ($query) use ($filters) {
                    return $query->whereHas('profile', function ($q) use ($filters) {
                        $q->where('parish', $filters['parish']);
                    });
                })
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get optimized customer dashboard data
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptimizedCustomerDashboard(int $limit = 10)
    {
        $cacheKey = $this->generateCacheKey('customer_dashboard', [], $limit);
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_DURATION, function () use ($limit) {
            Log::info("Executing optimized customer dashboard query", ['limit' => $limit]);
            
            return User::query()
                ->forDashboard()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get optimized package statistics for customers
     *
     * @param array $customerIds
     * @return array
     */
    public function getOptimizedPackageStatistics(array $customerIds): array
    {
        $cacheKey = $this->generateCacheKey('package_stats', ['customers' => $customerIds]);
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_DURATION, function () use ($customerIds) {
            Log::info("Executing optimized package statistics query", ['customer_count' => count($customerIds)]);
            
            if (empty($customerIds)) {
                return [];
            }

            $stats = DB::table('packages')
                ->whereIn('user_id', $customerIds)
                ->select([
                    'user_id',
                    DB::raw('COUNT(*) as total_packages'),
                    DB::raw('COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered_packages'),
                    DB::raw('COUNT(CASE WHEN status = "shipped" THEN 1 END) as in_transit_packages'),
                    DB::raw('COUNT(CASE WHEN status = "ready" THEN 1 END) as ready_packages'),
                    DB::raw('COALESCE(SUM(freight_price + customs_duty + storage_fee + delivery_fee), 0) as total_spent'),
                    DB::raw('COALESCE(AVG(freight_price + customs_duty + storage_fee + delivery_fee), 0) as avg_package_cost'),
                    DB::raw('COALESCE(SUM(weight), 0) as total_weight'),
                    DB::raw('COALESCE(AVG(weight), 0) as avg_weight')
                ])
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
                ->toArray();

            return $stats;
        });
    }

    /**
     * Get optimized financial summary for customers
     *
     * @param array $customerIds
     * @return array
     */
    public function getOptimizedFinancialSummary(array $customerIds): array
    {
        $cacheKey = $this->generateCacheKey('financial_summary', ['customers' => $customerIds]);
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_DURATION, function () use ($customerIds) {
            Log::info("Executing optimized financial summary query", ['customer_count' => count($customerIds)]);
            
            if (empty($customerIds)) {
                return [];
            }

            $financial = DB::table('packages')
                ->whereIn('user_id', $customerIds)
                ->select([
                    'user_id',
                    DB::raw('COALESCE(SUM(freight_price), 0) as total_freight'),
                    DB::raw('COALESCE(SUM(customs_duty), 0) as total_customs'),
                    DB::raw('COALESCE(SUM(storage_fee), 0) as total_storage'),
                    DB::raw('COALESCE(SUM(delivery_fee), 0) as total_delivery'),
                    DB::raw('COUNT(*) as package_count'),
                    DB::raw('COALESCE(AVG(freight_price), 0) as avg_freight'),
                    DB::raw('COALESCE(AVG(customs_duty), 0) as avg_customs'),
                    DB::raw('COALESCE(AVG(storage_fee), 0) as avg_storage'),
                    DB::raw('COALESCE(AVG(delivery_fee), 0) as avg_delivery')
                ])
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
                ->toArray();

            return $financial;
        });
    }

    /**
     * Get optimized recent activity for customers
     *
     * @param array $customerIds
     * @param int $days
     * @return array
     */
    public function getOptimizedRecentActivity(array $customerIds, int $days = 30): array
    {
        $cacheKey = $this->generateCacheKey('recent_activity', ['customers' => $customerIds, 'days' => $days]);
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_DURATION, function () use ($customerIds, $days) {
            Log::info("Executing optimized recent activity query", ['customer_count' => count($customerIds), 'days' => $days]);
            
            if (empty($customerIds)) {
                return [];
            }

            $cutoffDate = now()->subDays($days);

            $activity = DB::table('packages')
                ->whereIn('user_id', $customerIds)
                ->where('created_at', '>=', $cutoffDate)
                ->select([
                    'user_id',
                    DB::raw('COUNT(*) as recent_packages'),
                    DB::raw('MAX(created_at) as last_package_date'),
                    DB::raw('COALESCE(SUM(freight_price + customs_duty + storage_fee + delivery_fee), 0) as recent_spending')
                ])
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
                ->toArray();

            return $activity;
        });
    }

    /**
     * Execute bulk customer operations with optimization
     *
     * @param array $customerIds
     * @param string $operation
     * @param array $data
     * @return array
     */
    public function executeBulkCustomerOperation(array $customerIds, string $operation, array $data = []): array
    {
        Log::info("Executing bulk customer operation", ['operation' => $operation, 'customer_count' => count($customerIds)]);
        
        $results = [];
        $batchSize = 50; // Process in batches to avoid memory issues
        $batches = array_chunk($customerIds, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            Log::info("Processing batch", ['batch' => $batchIndex + 1, 'size' => count($batch)]);
            
            try {
                switch ($operation) {
                    case 'delete':
                        $results = array_merge($results, $this->bulkDeleteCustomers($batch));
                        break;
                    case 'restore':
                        $results = array_merge($results, $this->bulkRestoreCustomers($batch));
                        break;
                    case 'update':
                        $results = array_merge($results, $this->bulkUpdateCustomers($batch, $data));
                        break;
                    default:
                        throw new \InvalidArgumentException("Unknown operation: {$operation}");
                }
            } catch (\Exception $e) {
                Log::error("Batch operation failed", ['batch' => $batchIndex + 1, 'error' => $e->getMessage()]);
                $results[] = ['error' => "Batch " . ($batchIndex + 1) . " failed: " . $e->getMessage()];
            }
        }

        // Clear relevant caches after bulk operations
        $this->clearBulkOperationCaches($customerIds);

        return $results;
    }

    /**
     * Bulk delete customers
     *
     * @param array $customerIds
     * @return array
     */
    private function bulkDeleteCustomers(array $customerIds): array
    {
        $results = [];
        
        DB::transaction(function () use ($customerIds, &$results) {
            $customers = User::whereIn('id', $customerIds)->get();
            
            foreach ($customers as $customer) {
                try {
                    if ($customer->canBeDeleted()) {
                        $customer->softDeleteCustomer();
                        $results[] = ['success' => "Customer {$customer->id} deleted"];
                    } else {
                        $results[] = ['error' => "Customer {$customer->id} cannot be deleted"];
                    }
                } catch (\Exception $e) {
                    $results[] = ['error' => "Failed to delete customer {$customer->id}: " . $e->getMessage()];
                }
            }
        });

        return $results;
    }

    /**
     * Bulk restore customers
     *
     * @param array $customerIds
     * @return array
     */
    private function bulkRestoreCustomers(array $customerIds): array
    {
        $results = [];
        
        DB::transaction(function () use ($customerIds, &$results) {
            $customers = User::withTrashed()->whereIn('id', $customerIds)->get();
            
            foreach ($customers as $customer) {
                try {
                    if ($customer->canBeRestored()) {
                        $customer->restoreCustomer();
                        $results[] = ['success' => "Customer {$customer->id} restored"];
                    } else {
                        $results[] = ['error' => "Customer {$customer->id} cannot be restored"];
                    }
                } catch (\Exception $e) {
                    $results[] = ['error' => "Failed to restore customer {$customer->id}: " . $e->getMessage()];
                }
            }
        });

        return $results;
    }

    /**
     * Bulk update customers
     *
     * @param array $customerIds
     * @param array $data
     * @return array
     */
    private function bulkUpdateCustomers(array $customerIds, array $data): array
    {
        $results = [];
        
        DB::transaction(function () use ($customerIds, $data, &$results) {
            $updateCount = User::whereIn('id', $customerIds)->update($data);
            $results[] = ['success' => "Updated {$updateCount} customers"];
        });

        return $results;
    }

    /**
     * Clear caches related to bulk operations
     *
     * @param array $customerIds
     * @return void
     */
    private function clearBulkOperationCaches(array $customerIds): void
    {
        // Clear customer list caches
        Cache::forget('customer_list_*');
        Cache::forget('customer_search_*');
        Cache::forget('customer_dashboard_*');
        
        // Clear specific customer caches
        foreach ($customerIds as $customerId) {
            Cache::forget("customer_stats_{$customerId}");
            Cache::forget("customer_financial_{$customerId}");
            Cache::forget("customer_packages_{$customerId}");
            Cache::forget("customer_patterns_{$customerId}");
        }
        
        Log::info("Cleared bulk operation caches", ['customer_count' => count($customerIds)]);
    }

    /**
     * Generate cache key for queries
     *
     * @param string $type
     * @param array $params
     * @param int $limit
     * @param int $page
     * @return string
     */
    private function generateCacheKey(string $type, array $params = [], int $limit = 0, int $page = 1): string
    {
        $keyParts = [$type];
        
        if (!empty($params)) {
            ksort($params);
            $keyParts[] = md5(serialize($params));
        }
        
        if ($limit > 0) {
            $keyParts[] = "limit_{$limit}";
        }
        
        if ($page > 1) {
            $keyParts[] = "page_{$page}";
        }
        
        return implode('_', $keyParts);
    }

    /**
     * Get query optimization statistics
     *
     * @return array
     */
    public function getOptimizationStatistics(): array
    {
        return [
            'cache_hits' => Cache::get('query_cache_hits', 0),
            'cache_misses' => Cache::get('query_cache_misses', 0),
            'avg_query_time' => Cache::get('avg_query_time', 0),
            'slow_queries' => Cache::get('slow_queries_count', 0),
            'optimized_queries_today' => Cache::get('optimized_queries_' . now()->format('Y-m-d'), 0),
        ];
    }

    /**
     * Clear all query optimization caches
     *
     * @return void
     */
    public function clearAllQueryCaches(): void
    {
        $patterns = [
            'customer_list_*',
            'customer_search_*',
            'customer_dashboard_*',
            'package_stats_*',
            'financial_summary_*',
            'recent_activity_*'
        ];
        
        foreach ($patterns as $pattern) {
            // This is a simplified approach - in production you might want to use cache tags
            Cache::forget($pattern);
        }
        
        Log::info("Cleared all query optimization caches");
    }
}
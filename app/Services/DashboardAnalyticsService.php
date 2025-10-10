<?php

namespace App\Services;

use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardAnalyticsService
{
    protected DashboardCacheService $cacheService;

    public function __construct(DashboardCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get customer metrics with growth comparisons (excluding admin users)
     */
    public function getCustomerMetrics(array $filters): array
    {
        $cacheKey = $this->cacheKey('customer_metrics', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            $previousRange = $this->getPreviousDateRange($filters);

            // Only count customers, exclude admins and staff
            $customerQuery = User::customerUsers();

            // Current period metrics
            $currentCustomers = $customerQuery->clone()
                ->whereBetween('created_at', $dateRange)
                ->count();
                
            $totalCustomers = $customerQuery->clone()->count();
            
            $activeCustomers = $customerQuery->clone()
                ->whereNotNull('email_verified_at')
                ->count();

            // Previous period for comparison
            $previousCustomers = $customerQuery->clone()
                ->whereBetween('created_at', $previousRange)
                ->count();
            
            $growthPercentage = $previousCustomers > 0 
                ? (($currentCustomers - $previousCustomers) / $previousCustomers) * 100 
                : 0;

            return [
                'total' => $totalCustomers,
                'active' => $activeCustomers,
                'new_this_period' => $currentCustomers,
                'growth_percentage' => round($growthPercentage, 2),
                'inactive' => $totalCustomers - $activeCustomers,
            ];
        });
    }

    /**
     * Get shipment and package metrics using correct PackageStatus enum values
     */
    public function getShipmentMetrics(array $filters): array
    {
        $cacheKey = $this->cacheKey('shipment_metrics', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            \Log::info('DashboardAnalyticsService getShipmentMetrics start', [
                'filters' => $filters,
                'cache_key' => $this->cacheKey('shipment_metrics', $filters)
            ]);
            
            $dateRange = $this->getDateRange($filters);
            \Log::info('Date range calculated', [
                'start' => $dateRange[0],
                'end' => $dateRange[1]
            ]);

            // Build base query with date range
            $baseQuery = Package::whereBetween('created_at', $dateRange);
            
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                \Log::info('Applying service type filters', ['service_types' => $filters['service_types']]);
                $baseQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
            } else {
                \Log::info('No service type filters applied', [
                    'service_types_isset' => isset($filters['service_types']),
                    'service_types_empty' => empty($filters['service_types']),
                    'service_types_value' => $filters['service_types'] ?? 'not_set'
                ]);
            }

            $totalPackages = $baseQuery->count();
            \Log::info('Total packages after base query', ['count' => $totalPackages]);
            
            // Use correct PackageStatus enum values with service type filtering
            $statusQuery = Package::whereBetween('created_at', $dateRange);
            
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $statusQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
            }
            
            $packagesByStatus = $statusQuery
                ->selectRaw('
                    COUNT(CASE WHEN status = ? THEN 1 END) as shipped,
                    COUNT(CASE WHEN status = ? THEN 1 END) as delivered,
                    COUNT(CASE WHEN status = ? THEN 1 END) as delayed_count,
                    COUNT(CASE WHEN status = ? THEN 1 END) as pending,
                    COUNT(CASE WHEN status = ? THEN 1 END) as processing,
                    COUNT(CASE WHEN status = ? THEN 1 END) as ready,
                    COUNT(CASE WHEN status = ? THEN 1 END) as customs
                ', [
                    \App\Enums\PackageStatus::SHIPPED,
                    \App\Enums\PackageStatus::DELIVERED,
                    \App\Enums\PackageStatus::DELAYED,
                    \App\Enums\PackageStatus::PENDING,
                    \App\Enums\PackageStatus::PROCESSING,
                    \App\Enums\PackageStatus::READY,
                    \App\Enums\PackageStatus::CUSTOMS
                ])
                ->first()
                ->toArray();
                
            \Log::info('Status query results', [
                'status_counts' => $packagesByStatus,
                'enum_constants' => [
                    'SHIPPED' => \App\Enums\PackageStatus::SHIPPED,
                    'DELIVERED' => \App\Enums\PackageStatus::DELIVERED,
                    'DELAYED' => \App\Enums\PackageStatus::DELAYED,
                    'PENDING' => \App\Enums\PackageStatus::PENDING,
                    'PROCESSING' => \App\Enums\PackageStatus::PROCESSING,
                    'READY' => \App\Enums\PackageStatus::READY,
                    'CUSTOMS' => \App\Enums\PackageStatus::CUSTOMS
                ]
            ]);

            // Calculate in_transit as shipped + customs
            $inTransit = ($packagesByStatus['shipped'] ?? 0) + ($packagesByStatus['customs'] ?? 0);

            // Calculate average processing time for completed packages (ready or delivered)
            $completedQuery = Package::whereBetween('created_at', $dateRange)
                ->whereIn('status', [\App\Enums\PackageStatus::READY, \App\Enums\PackageStatus::DELIVERED]);
                
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $completedQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
            }
            
            $completedPackages = $completedQuery
                ->select('created_at', 'updated_at')
                ->get();
                
            $avgProcessingTime = 0;
            if ($completedPackages->count() > 0) {
                $totalDays = $completedPackages->sum(function ($package) {
                    return $package->created_at->diffInDays($package->updated_at);
                });
                $avgProcessingTime = $totalDays / $completedPackages->count();
            }

            return [
                'total' => $totalPackages,
                'in_transit' => $inTransit,
                'delivered' => $packagesByStatus['delivered'] ?? 0,
                'delayed' => $packagesByStatus['delayed_count'] ?? 0,
                'pending' => $packagesByStatus['pending'] ?? 0,
                'processing' => $packagesByStatus['processing'] ?? 0,
                'ready' => $packagesByStatus['ready'] ?? 0,
                'customs' => $packagesByStatus['customs'] ?? 0,
                'shipped' => $packagesByStatus['shipped'] ?? 0,
                'processing_time_avg' => round($avgProcessingTime, 1),
                'status_distribution' => $packagesByStatus,
            ];
        });
    }

    /**
     * Get financial metrics and revenue data using actual customer transactions
     */
    public function getFinancialMetrics(array $filters): array
    {
        $cacheKey = $this->cacheKey('financial_metrics', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            $previousRange = $this->getPreviousDateRange($filters);

            // Current period revenue from service charges only
            // Revenue = charges made for services (what the business actually earned)
            // Note: We don't count payments as they just cover the charges
            // Get customer role ID for join
            $customerRole = \App\Models\Role::where('name', 'customer')->first();
            $customerRoleId = $customerRole ? $customerRole->id : 3; // fallback to 3 if role not found
            
            $revenueQuery = DB::table('customer_transactions')
                ->join('users', 'customer_transactions.user_id', '=', 'users.id')
                ->where('users.role_id', $customerRoleId) // Only customers, not admins
                ->whereBetween('customer_transactions.created_at', $dateRange)
                ->where('customer_transactions.type', \App\Models\CustomerTransaction::TYPE_CHARGE);
                
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $revenueQuery->join('packages', function($join) {
                    $join->on('customer_transactions.reference_id', '=', 'packages.id')
                         ->where('customer_transactions.reference_type', '=', 'package_distribution');
                })
                ->join('manifests', 'packages.manifest_id', '=', 'manifests.id')
                ->whereIn('manifests.type', $filters['service_types']);
            }
            
            $currentRevenue = $revenueQuery->sum('customer_transactions.amount') ?? 0;

            // Previous period revenue from service charges only
            $previousRevenueQuery = DB::table('customer_transactions')
                ->join('users', 'customer_transactions.user_id', '=', 'users.id')
                ->where('users.role_id', $customerRoleId) // Only customers, not admins
                ->whereBetween('customer_transactions.created_at', $previousRange)
                ->where('customer_transactions.type', \App\Models\CustomerTransaction::TYPE_CHARGE);
                
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $previousRevenueQuery->join('packages', function($join) {
                    $join->on('customer_transactions.reference_id', '=', 'packages.id')
                         ->where('customer_transactions.reference_type', '=', 'package_distribution');
                })
                ->join('manifests', 'packages.manifest_id', '=', 'manifests.id')
                ->whereIn('manifests.type', $filters['service_types']);
            }
            
            $previousRevenue = $previousRevenueQuery->sum('customer_transactions.amount') ?? 0;

            $growthPercentage = $previousRevenue > 0 
                ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
                : 0;

            // Count unique orders (package distributions) for average order value
            // Use charges only to avoid double counting
            $ordersQuery = DB::table('customer_transactions')
                ->join('users', 'customer_transactions.user_id', '=', 'users.id')
                ->where('users.role_id', $customerRoleId) // Only customers, not admins
                ->whereBetween('customer_transactions.created_at', $dateRange)
                ->where('customer_transactions.reference_type', 'package_distribution')
                ->where('customer_transactions.type', \App\Models\CustomerTransaction::TYPE_CHARGE);
                
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $ordersQuery->join('packages', 'customer_transactions.reference_id', '=', 'packages.id')
                ->join('manifests', 'packages.manifest_id', '=', 'manifests.id')
                ->whereIn('manifests.type', $filters['service_types']);
            }
            
            $totalOrders = $ordersQuery
                ->distinct('customer_transactions.reference_id')
                ->count('customer_transactions.reference_id');
            
            $averageOrderValue = $totalOrders > 0 ? $currentRevenue / $totalOrders : 0;

            return [
                'current_period' => $currentRevenue,
                'previous_period' => $previousRevenue,
                'growth_percentage' => round($growthPercentage, 2),
                'average_order_value' => round($averageOrderValue, 2),
                'total_orders' => $totalOrders,
            ];
        });
    }

    /**
     * Get customer growth data for charts
     */
    public function getCustomerGrowthData(array $filters): array
    {
        $cacheKey = $this->cacheKey('customer_growth', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $growthData = User::whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'count' => $item->count,
                    ];
                })
                ->toArray();

            return $growthData;
        });
    }

    /**
     * Get revenue analytics data for charts
     */
    public function getRevenueAnalytics(array $filters): array
    {
        $cacheKey = $this->cacheKey('revenue_analytics', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $revenueData = Package::whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(created_at) as date, SUM(freight_price + clearance_fee + storage_fee + delivery_fee) as revenue, COUNT(*) as orders')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'revenue' => (float) $item->revenue,
                        'orders' => $item->orders,
                    ];
                })
                ->toArray();

            return $revenueData;
        });
    }

    /**
     * Get shipment volume trend data
     */
    public function getShipmentVolumeData(array $filters): array
    {
        $cacheKey = $this->cacheKey('shipment_volume', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $volumeQuery = Package::whereBetween('created_at', $dateRange);
            
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $volumeQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
            }
            
            $volumeData = $volumeQuery
                ->selectRaw('DATE(created_at) as date, COUNT(*) as volume, SUM(weight) as total_weight')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'volume' => $item->volume,
                        'weight' => (float) $item->total_weight,
                    ];
                })
                ->toArray();

            return $volumeData;
        });
    }

    /**
     * Get package status distribution over time
     */
    public function getPackageStatusDistribution(array $filters): array
    {
        $cacheKey = $this->cacheKey('package_status_distribution', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $statusQuery = Package::whereBetween('created_at', $dateRange);
            
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $statusQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
            }
            
            $statusData = $statusQuery
                ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get()
                ->groupBy('date')
                ->map(function ($dayData, $date) {
                    $statusCounts = $dayData->pluck('count', 'status')->toArray();
                    return [
                        'date' => $date,
                        'pending' => $statusCounts['pending'] ?? 0,
                        'processing' => $statusCounts['processing'] ?? 0,
                        'in_transit' => $statusCounts['in_transit'] ?? 0,
                        'shipped' => $statusCounts['shipped'] ?? 0,
                        'ready_for_pickup' => $statusCounts['ready_for_pickup'] ?? 0,
                        'delivered' => $statusCounts['delivered'] ?? 0,
                        'delayed' => $statusCounts['delayed'] ?? 0,
                    ];
                })
                ->values()
                ->toArray();

            return $statusData;
        });
    }

    /**
     * Get processing time analysis data
     */
    public function getProcessingTimeAnalysis(array $filters): array
    {
        $cacheKey = $this->cacheKey('processing_time_analysis', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $processingQuery = Package::whereBetween('created_at', $dateRange)
                ->whereIn('status', ['ready_for_pickup', 'delivered']);
                
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $processingQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
            }
            
            $processingData = $processingQuery
                ->select('created_at', 'updated_at', 'status')
                ->get()
                ->map(function ($package) {
                    $processingDays = $package->created_at->diffInDays($package->updated_at);
                    return [
                        'days' => $processingDays,
                        'status' => $package->status,
                        'date' => $package->created_at->format('Y-m-d'),
                    ];
                })
                ->groupBy('date')
                ->map(function ($dayData, $date) {
                    $times = $dayData->pluck('days');
                    return [
                        'date' => $date,
                        'avg_processing_time' => $times->avg(),
                        'min_processing_time' => $times->min(),
                        'max_processing_time' => $times->max(),
                        'count' => $times->count(),
                    ];
                })
                ->values()
                ->toArray();

            return $processingData;
        });
    }

    /**
     * Get shipping method breakdown
     */
    public function getShippingMethodBreakdown(array $filters): array
    {
        $cacheKey = $this->cacheKey('shipping_method_breakdown', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $methodQuery = Package::whereBetween('packages.created_at', $dateRange)
                ->join('manifests', 'packages.manifest_id', '=', 'manifests.id');
                
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $methodQuery->whereIn('manifests.type', $filters['service_types']);
            }
            
            $methodData = $methodQuery
                ->select('manifests.type', DB::raw('COUNT(*) as count'), DB::raw('SUM(packages.weight) as total_weight'))
                ->groupBy('manifests.type')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => ucfirst($item->type),
                        'count' => $item->count,
                        'weight' => (float) $item->total_weight,
                        'percentage' => 0,
                    ];
                })
                ->toArray();

            // Calculate percentages
            $totalCount = collect($methodData)->sum('count');
            if ($totalCount > 0) {
                foreach ($methodData as &$method) {
                    $method['percentage'] = round(($method['count'] / $totalCount) * 100, 1);
                }
            }

            return $methodData;
        });
    }

    /**
     * Get delivery performance metrics
     */
    public function getDeliveryPerformanceMetrics(array $filters): array
    {
        $cacheKey = $this->cacheKey('delivery_performance', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $totalQuery = Package::whereBetween('created_at', $dateRange);
            $deliveredQuery = Package::whereBetween('created_at', $dateRange)
                ->whereIn('status', ['delivered', 'ready_for_pickup']);
            $delayedQuery = Package::whereBetween('created_at', $dateRange)
                ->where('status', 'delayed');
                
            // Apply service type filters if specified
            if (isset($filters['service_types']) && !empty($filters['service_types'])) {
                $totalQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
                $deliveredQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
                $delayedQuery->whereHas('manifest', function($query) use ($filters) {
                    $query->whereIn('type', $filters['service_types']);
                });
            }
            
            $totalPackages = $totalQuery->count();
            $deliveredPackages = $deliveredQuery->count();
            $delayedPackages = $delayedQuery->count();
            
            $onTimeDeliveryRate = $totalPackages > 0 
                ? round((($deliveredPackages - $delayedPackages) / $totalPackages) * 100, 1)
                : 0;
            
            $deliveryRate = $totalPackages > 0 
                ? round(($deliveredPackages / $totalPackages) * 100, 1)
                : 0;

            return [
                'total_packages' => $totalPackages,
                'delivered_packages' => $deliveredPackages,
                'delayed_packages' => $delayedPackages,
                'on_time_delivery_rate' => $onTimeDeliveryRate,
                'overall_delivery_rate' => $deliveryRate,
            ];
        });
    }

    /**
     * Generate cache key for dashboard data with normalized filters
     */
    public function cacheKey(string $type, array $filters): string
    {
        // Normalize filters to ensure consistent cache keys
        $normalizedFilters = $this->normalizeFilters($filters);
        $filterHash = md5(serialize($normalizedFilters));
        $cacheKey = "dashboard.{$type}.{$filterHash}";
        
        \Log::info('Cache key generated', [
            'type' => $type,
            'original_filters' => $filters,
            'normalized_filters' => $normalizedFilters,
            'serialized' => serialize($normalizedFilters),
            'hash' => $filterHash,
            'cache_key' => $cacheKey
        ]);
        
        return $cacheKey;
    }

    /**
     * Normalize filters to ensure consistent cache key generation
     */
    protected function normalizeFilters(array $filters): array
    {
        // Start with default values
        $normalized = [
            'date_range' => '30',
            'custom_start' => null,
            'custom_end' => null,
            'service_types' => []
        ];

        // Override with provided values, ensuring consistent types
        if (isset($filters['date_range'])) {
            $normalized['date_range'] = (string) $filters['date_range'];
        }

        if (isset($filters['custom_start']) && !empty($filters['custom_start'])) {
            $normalized['custom_start'] = (string) $filters['custom_start'];
        }

        if (isset($filters['custom_end']) && !empty($filters['custom_end'])) {
            $normalized['custom_end'] = (string) $filters['custom_end'];
        }

        if (isset($filters['service_types'])) {
            // Ensure service_types is always an array and sort for consistency
            $serviceTypes = is_array($filters['service_types']) ? $filters['service_types'] : [];
            // Remove empty values and sort
            $serviceTypes = array_filter($serviceTypes, function($value) {
                return !empty($value);
            });
            sort($serviceTypes);
            $normalized['service_types'] = $serviceTypes;
        }

        // Sort keys for consistent serialization
        ksort($normalized);

        return $normalized;
    }

    /**
     * Invalidate cache patterns
     */
    public function invalidateCache(string $pattern): void
    {
        $this->cacheService->flush($pattern);
    }

    /**
     * Clear all dashboard cache
     */
    public function clearDashboardCache(): void
    {
        // Clear all dashboard-related cache keys
        $patterns = [
            'dashboard.customer_metrics.*',
            'dashboard.shipment_metrics.*',
            'dashboard.financial_metrics.*',
            'dashboard.customer_growth.*',
            'dashboard.revenue_analytics.*',
            'dashboard.shipment_volume.*',
            'dashboard.package_status_distribution.*',
            'dashboard.processing_time_analysis.*',
            'dashboard.shipping_method_breakdown.*',
            'dashboard.delivery_performance.*'
        ];

        foreach ($patterns as $pattern) {
            $this->invalidateCache($pattern);
        }
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        $days = $filters['date_range'] ?? 30;
        
        // Check if custom dates are provided and not empty
        if (isset($filters['custom_start']) && isset($filters['custom_end']) 
            && !empty($filters['custom_start']) && !empty($filters['custom_end'])) {
            return [
                Carbon::parse($filters['custom_start'])->startOfDay(),
                Carbon::parse($filters['custom_end'])->endOfDay(),
            ];
        }

        return [
            Carbon::now()->subDays($days)->startOfDay(),
            Carbon::now()->endOfDay(),
        ];
    }

    /**
     * Get previous date range for comparison
     */
    protected function getPreviousDateRange(array $filters): array
    {
        $days = $filters['date_range'] ?? 30;
        
        // Check if custom dates are provided and not empty
        if (isset($filters['custom_start']) && isset($filters['custom_end']) 
            && !empty($filters['custom_start']) && !empty($filters['custom_end'])) {
            $start = Carbon::parse($filters['custom_start']);
            $end = Carbon::parse($filters['custom_end']);
            $duration = $start->diffInDays($end);
            
            return [
                $start->copy()->subDays($duration + 1)->startOfDay(),
                $start->copy()->subDay()->endOfDay(),
            ];
        }

        return [
            Carbon::now()->subDays($days * 2)->startOfDay(),
            Carbon::now()->subDays($days)->endOfDay(),
        ];
    }
}
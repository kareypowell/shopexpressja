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
            $dateRange = $this->getDateRange($filters);

            $totalPackages = Package::whereBetween('created_at', $dateRange)->count();
            
            // Use correct PackageStatus enum values
            $packagesByStatus = Package::whereBetween('created_at', $dateRange)
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

            // Calculate in_transit as shipped + customs
            $inTransit = ($packagesByStatus['shipped'] ?? 0) + ($packagesByStatus['customs'] ?? 0);

            // Calculate average processing time for completed packages (ready or delivered)
            $completedPackages = Package::whereBetween('created_at', $dateRange)
                ->whereIn('status', [\App\Enums\PackageStatus::READY, \App\Enums\PackageStatus::DELIVERED])
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
            
            $currentRevenue = DB::table('customer_transactions')
                ->join('users', 'customer_transactions.user_id', '=', 'users.id')
                ->where('users.role_id', $customerRoleId) // Only customers, not admins
                ->whereBetween('customer_transactions.created_at', $dateRange)
                ->where('customer_transactions.type', \App\Models\CustomerTransaction::TYPE_CHARGE)
                ->sum('customer_transactions.amount') ?? 0;

            // Previous period revenue from service charges only
            $previousRevenue = DB::table('customer_transactions')
                ->join('users', 'customer_transactions.user_id', '=', 'users.id')
                ->where('users.role_id', $customerRoleId) // Only customers, not admins
                ->whereBetween('customer_transactions.created_at', $previousRange)
                ->where('customer_transactions.type', \App\Models\CustomerTransaction::TYPE_CHARGE)
                ->sum('customer_transactions.amount') ?? 0;

            $growthPercentage = $previousRevenue > 0 
                ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
                : 0;

            // Count unique orders (package distributions) for average order value
            // Use charges only to avoid double counting
            $totalOrders = DB::table('customer_transactions')
                ->join('users', 'customer_transactions.user_id', '=', 'users.id')
                ->where('users.role_id', $customerRoleId) // Only customers, not admins
                ->whereBetween('customer_transactions.created_at', $dateRange)
                ->where('customer_transactions.reference_type', 'package_distribution')
                ->where('customer_transactions.type', \App\Models\CustomerTransaction::TYPE_CHARGE)
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
            
            $volumeData = Package::whereBetween('created_at', $dateRange)
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
            
            $statusData = Package::whereBetween('created_at', $dateRange)
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
            
            $processingData = Package::whereBetween('created_at', $dateRange)
                ->whereIn('status', ['ready_for_pickup', 'delivered'])
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
            
            $methodData = Package::whereBetween('packages.created_at', $dateRange)
                ->join('manifests', 'packages.manifest_id', '=', 'manifests.id')
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
            
            $totalPackages = Package::whereBetween('created_at', $dateRange)->count();
            $deliveredPackages = Package::whereBetween('created_at', $dateRange)
                ->whereIn('status', ['delivered', 'ready_for_pickup'])
                ->count();
            $delayedPackages = Package::whereBetween('created_at', $dateRange)
                ->where('status', 'delayed')
                ->count();
            
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
     * Generate cache key for dashboard data
     */
    public function cacheKey(string $type, array $filters): string
    {
        $filterHash = md5(serialize($filters));
        return "dashboard.{$type}.{$filterHash}";
    }

    /**
     * Invalidate cache patterns
     */
    public function invalidateCache(string $pattern): void
    {
        $this->cacheService->flush($pattern);
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        $days = $filters['date_range'] ?? 30;
        
        if (isset($filters['custom_start']) && isset($filters['custom_end'])) {
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
        
        if (isset($filters['custom_start']) && isset($filters['custom_end'])) {
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
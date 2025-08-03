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
     * Get customer metrics with growth comparisons
     */
    public function getCustomerMetrics(array $filters): array
    {
        $cacheKey = $this->cacheKey('customer_metrics', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            $previousRange = $this->getPreviousDateRange($filters);

            // Current period metrics
            $currentCustomers = User::whereBetween('created_at', $dateRange)->count();
            $totalCustomers = User::count();
            $activeCustomers = User::whereNotNull('email_verified_at')
                ->where('deleted_at', null)
                ->count();

            // Previous period for comparison
            $previousCustomers = User::whereBetween('created_at', $previousRange)->count();
            
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
     * Get shipment and package metrics
     */
    public function getShipmentMetrics(array $filters): array
    {
        $cacheKey = $this->cacheKey('shipment_metrics', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);

            $totalPackages = Package::whereBetween('created_at', $dateRange)->count();
            
            $packagesByStatus = Package::whereBetween('created_at', $dateRange)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Calculate average processing time (using updated_at as proxy for completion)
            $completedPackages = Package::whereBetween('created_at', $dateRange)
                ->where('status', 'ready_for_pickup')
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
                'in_transit' => $packagesByStatus['in_transit'] ?? 0,
                'delivered' => $packagesByStatus['delivered'] ?? 0,
                'delayed' => $packagesByStatus['delayed'] ?? 0,
                'pending' => $packagesByStatus['pending'] ?? 0,
                'processing_time_avg' => round($avgProcessingTime, 1),
                'status_distribution' => $packagesByStatus,
            ];
        });
    }

    /**
     * Get financial metrics and revenue data
     */
    public function getFinancialMetrics(array $filters): array
    {
        $cacheKey = $this->cacheKey('financial_metrics', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            $previousRange = $this->getPreviousDateRange($filters);

            // Current period revenue (sum of all cost components)
            $currentRevenue = Package::whereBetween('created_at', $dateRange)
                ->selectRaw('SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_revenue')
                ->value('total_revenue') ?? 0;

            // Previous period revenue
            $previousRevenue = Package::whereBetween('created_at', $previousRange)
                ->selectRaw('SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_revenue')
                ->value('total_revenue') ?? 0;

            $growthPercentage = $previousRevenue > 0 
                ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
                : 0;

            // Average order value
            $packageCount = Package::whereBetween('created_at', $dateRange)
                ->where(function($query) {
                    $query->whereNotNull('freight_price')
                          ->orWhereNotNull('customs_duty')
                          ->orWhereNotNull('storage_fee')
                          ->orWhereNotNull('delivery_fee');
                })
                ->count();
            
            $averageOrderValue = $packageCount > 0 ? $currentRevenue / $packageCount : 0;

            return [
                'current_period' => $currentRevenue,
                'previous_period' => $previousRevenue,
                'growth_percentage' => round($growthPercentage, 2),
                'average_order_value' => round($averageOrderValue, 2),
                'total_orders' => $packageCount,
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
                ->selectRaw('DATE(created_at) as date, SUM(freight_price + customs_duty + storage_fee + delivery_fee) as revenue, COUNT(*) as orders')
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
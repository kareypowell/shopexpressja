<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardCacheService;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialAnalytics extends Component
{
    public $dateRange = '30';
    public $customStartDate = '';
    public $customEndDate = '';
    public $isLoading = false;

    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
        'refreshDashboard' => 'refreshData'
    ];

    protected DashboardAnalyticsService $analyticsService;
    protected DashboardCacheService $cacheService;

    public function boot(DashboardAnalyticsService $analyticsService, DashboardCacheService $cacheService)
    {
        $this->analyticsService = $analyticsService;
        $this->cacheService = $cacheService;
    }

    public function mount()
    {
        $this->refreshData();
    }

    public function updateFilters($filters)
    {
        $this->dateRange = $filters['date_range'] ?? '30';
        $this->customStartDate = $filters['custom_start'] ?? '';
        $this->customEndDate = $filters['custom_end'] ?? '';
        $this->refreshData();
    }

    public function refreshData()
    {
        $this->isLoading = true;
        $this->emit('dataRefreshed');
        $this->isLoading = false;
    }

    /**
     * Get revenue trend data over configurable periods
     */
    public function getRevenueTrendData()
    {
        $filters = $this->getFilters();
        $cacheKey = $this->analyticsService->cacheKey('revenue_trends', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            // Group by day, week, or month based on date range
            $groupBy = $this->getGroupByPeriod($filters);
            $dateFormat = $this->getDateFormat($groupBy);
            
            $revenueData = Package::whereBetween('created_at', $dateRange)
                ->selectRaw("
                    {$dateFormat} as period,
                    SUM(freight_price) as freight_revenue,
                    SUM(customs_duty) as customs_revenue,
                    SUM(storage_fee) as storage_revenue,
                    SUM(delivery_fee) as delivery_revenue,
                    SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_revenue,
                    COUNT(*) as order_count,
                    AVG(freight_price + customs_duty + storage_fee + delivery_fee) as avg_order_value
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => $item->period,
                        'freight_revenue' => (float) ($item->freight_revenue ?? 0),
                        'customs_revenue' => (float) ($item->customs_revenue ?? 0),
                        'storage_revenue' => (float) ($item->storage_revenue ?? 0),
                        'delivery_revenue' => (float) ($item->delivery_revenue ?? 0),
                        'total_revenue' => (float) ($item->total_revenue ?? 0),
                        'order_count' => $item->order_count,
                        'avg_order_value' => round((float) ($item->avg_order_value ?? 0), 2),
                    ];
                })
                ->toArray();

            return $revenueData;
        });
    }

    /**
     * Get revenue breakdown by service type
     */
    public function getRevenueByServiceType()
    {
        $filters = $this->getFilters();
        $cacheKey = $this->analyticsService->cacheKey('revenue_by_service', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $serviceData = Package::whereBetween('packages.created_at', $dateRange)
                ->join('manifests', 'packages.manifest_id', '=', 'manifests.id')
                ->selectRaw("
                    manifests.type as service_type,
                    SUM(packages.freight_price + packages.customs_duty + packages.storage_fee + packages.delivery_fee) as total_revenue,
                    COUNT(*) as order_count,
                    AVG(packages.freight_price + packages.customs_duty + packages.storage_fee + packages.delivery_fee) as avg_order_value,
                    SUM(packages.freight_price) as freight_revenue,
                    SUM(packages.customs_duty) as customs_revenue,
                    SUM(packages.storage_fee) as storage_revenue,
                    SUM(packages.delivery_fee) as delivery_revenue
                ")
                ->groupBy('manifests.type')
                ->get()
                ->map(function ($item) {
                    return [
                        'service_type' => ucfirst($item->service_type),
                        'total_revenue' => (float) ($item->total_revenue ?? 0),
                        'order_count' => $item->order_count,
                        'avg_order_value' => round((float) ($item->avg_order_value ?? 0), 2),
                        'breakdown' => [
                            'freight' => (float) ($item->freight_revenue ?? 0),
                            'customs' => (float) ($item->customs_revenue ?? 0),
                            'storage' => (float) ($item->storage_revenue ?? 0),
                            'delivery' => (float) ($item->delivery_revenue ?? 0),
                        ],
                    ];
                })
                ->toArray();

            // Calculate percentages
            $totalRevenue = collect($serviceData)->sum('total_revenue');
            if ($totalRevenue > 0) {
                foreach ($serviceData as &$service) {
                    $service['percentage'] = round(($service['total_revenue'] / $totalRevenue) * 100, 1);
                }
            }

            return $serviceData;
        });
    }

    /**
     * Get revenue breakdown by customer segment
     */
    public function getRevenueByCustomerSegment()
    {
        $filters = $this->getFilters();
        $cacheKey = $this->analyticsService->cacheKey('revenue_by_customer_segment', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            // Define customer segments based on total spending
            $customerSegments = User::select('users.id')
                ->join('packages', 'users.id', '=', 'packages.user_id')
                ->selectRaw("
                    users.id,
                    SUM(packages.freight_price + packages.customs_duty + packages.storage_fee + packages.delivery_fee) as total_spent,
                    COUNT(packages.id) as package_count,
                    CASE 
                        WHEN SUM(packages.freight_price + packages.customs_duty + packages.storage_fee + packages.delivery_fee) >= 10000 THEN 'Premium'
                        WHEN SUM(packages.freight_price + packages.customs_duty + packages.storage_fee + packages.delivery_fee) >= 5000 THEN 'High Value'
                        WHEN SUM(packages.freight_price + packages.customs_duty + packages.storage_fee + packages.delivery_fee) >= 1000 THEN 'Regular'
                        ELSE 'New/Low Value'
                    END as segment
                ")
                ->whereBetween('packages.created_at', $dateRange)
                ->groupBy('users.id')
                ->get();

            $segmentData = $customerSegments->groupBy('segment')
                ->map(function ($customers, $segment) {
                    $totalRevenue = $customers->sum('total_spent');
                    $customerCount = $customers->count();
                    $totalPackages = $customers->sum('package_count');
                    
                    return [
                        'segment' => $segment,
                        'total_revenue' => (float) $totalRevenue,
                        'customer_count' => $customerCount,
                        'package_count' => $totalPackages,
                        'avg_revenue_per_customer' => $customerCount > 0 ? round($totalRevenue / $customerCount, 2) : 0,
                        'avg_order_value' => $totalPackages > 0 ? round($totalRevenue / $totalPackages, 2) : 0,
                    ];
                })
                ->values()
                ->toArray();

            // Calculate percentages
            $totalRevenue = collect($segmentData)->sum('total_revenue');
            if ($totalRevenue > 0) {
                foreach ($segmentData as &$segment) {
                    $segment['percentage'] = round(($segment['total_revenue'] / $totalRevenue) * 100, 1);
                }
            }

            return $segmentData;
        });
    }

    /**
     * Calculate key performance indicators (KPIs)
     */
    public function getFinancialKPIs()
    {
        $filters = $this->getFilters();
        $cacheKey = $this->analyticsService->cacheKey('financial_kpis', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            $previousRange = $this->getPreviousDateRange($filters);

            // Current period metrics
            $currentMetrics = Package::whereBetween('created_at', $dateRange)
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_revenue,
                    AVG(freight_price + customs_duty + storage_fee + delivery_fee) as avg_order_value,
                    COUNT(DISTINCT user_id) as unique_customers
                ")
                ->first();

            // Previous period metrics for comparison
            $previousMetrics = Package::whereBetween('created_at', $previousRange)
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_revenue,
                    AVG(freight_price + customs_duty + storage_fee + delivery_fee) as avg_order_value,
                    COUNT(DISTINCT user_id) as unique_customers
                ")
                ->first();

            // Calculate Customer Lifetime Value (CLV) - simplified version
            $avgCustomerLifespan = 12; // months - could be calculated from actual data
            $avgMonthlyOrders = $currentMetrics->total_orders / max(1, $this->getPeriodInMonths($filters));
            $clv = ($currentMetrics->avg_order_value ?? 0) * $avgMonthlyOrders * $avgCustomerLifespan;

            // Calculate growth rates
            $revenueGrowth = $this->calculateGrowthRate(
                $currentMetrics->total_revenue ?? 0,
                $previousMetrics->total_revenue ?? 0
            );
            
            $aovGrowth = $this->calculateGrowthRate(
                $currentMetrics->avg_order_value ?? 0,
                $previousMetrics->avg_order_value ?? 0
            );

            $customerGrowth = $this->calculateGrowthRate(
                $currentMetrics->unique_customers ?? 0,
                $previousMetrics->unique_customers ?? 0
            );

            // Calculate Average Revenue Per User (ARPU)
            $arpu = $currentMetrics->unique_customers > 0 
                ? ($currentMetrics->total_revenue ?? 0) / $currentMetrics->unique_customers 
                : 0;

            return [
                'total_revenue' => [
                    'current' => (float) ($currentMetrics->total_revenue ?? 0),
                    'previous' => (float) ($previousMetrics->total_revenue ?? 0),
                    'growth_rate' => $revenueGrowth,
                ],
                'average_order_value' => [
                    'current' => round((float) ($currentMetrics->avg_order_value ?? 0), 2),
                    'previous' => round((float) ($previousMetrics->avg_order_value ?? 0), 2),
                    'growth_rate' => $aovGrowth,
                ],
                'customer_lifetime_value' => [
                    'estimated_clv' => round($clv, 2),
                    'avg_lifespan_months' => $avgCustomerLifespan,
                    'avg_monthly_orders' => round($avgMonthlyOrders, 2),
                ],
                'customer_metrics' => [
                    'unique_customers' => $currentMetrics->unique_customers ?? 0,
                    'previous_customers' => $previousMetrics->unique_customers ?? 0,
                    'customer_growth' => $customerGrowth,
                    'arpu' => round($arpu, 2),
                ],
                'order_metrics' => [
                    'total_orders' => $currentMetrics->total_orders ?? 0,
                    'previous_orders' => $previousMetrics->total_orders ?? 0,
                    'order_growth' => $this->calculateGrowthRate(
                        $currentMetrics->total_orders ?? 0,
                        $previousMetrics->total_orders ?? 0
                    ),
                ],
            ];
        });
    }

    /**
     * Get profit margin analysis data
     */
    public function getProfitMarginAnalysis()
    {
        $filters = $this->getFilters();
        $cacheKey = $this->analyticsService->cacheKey('profit_margin_analysis', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            // For this analysis, we'll assume freight_price is our revenue
            // and other fees (customs, storage, delivery) are costs we collect but don't profit from
            $marginData = Package::whereBetween('created_at', $dateRange)
                ->selectRaw("
                    DATE(created_at) as date,
                    SUM(freight_price) as gross_revenue,
                    SUM(customs_duty + storage_fee + delivery_fee) as pass_through_costs,
                    SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_revenue,
                    COUNT(*) as order_count
                ")
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    $grossRevenue = (float) ($item->gross_revenue ?? 0);
                    $passThroughCosts = (float) ($item->pass_through_costs ?? 0);
                    $totalRevenue = (float) ($item->total_revenue ?? 0);
                    
                    // Assume 30% operational costs on freight revenue
                    $operationalCosts = $grossRevenue * 0.30;
                    $netProfit = $grossRevenue - $operationalCosts;
                    $profitMargin = $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0;
                    
                    return [
                        'date' => $item->date,
                        'gross_revenue' => $grossRevenue,
                        'operational_costs' => round($operationalCosts, 2),
                        'net_profit' => round($netProfit, 2),
                        'profit_margin' => round($profitMargin, 2),
                        'total_revenue' => $totalRevenue,
                        'pass_through_costs' => $passThroughCosts,
                        'order_count' => $item->order_count,
                    ];
                })
                ->toArray();

            return $marginData;
        });
    }

    /**
     * Get customer lifetime value scatter plot data
     */
    public function getCustomerLifetimeValueData()
    {
        $filters = $this->getFilters();
        $cacheKey = $this->analyticsService->cacheKey('customer_clv_data', $filters);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            
            $clvData = User::select('users.id', 'users.created_at')
                ->join('packages', 'users.id', '=', 'packages.user_id')
                ->selectRaw("
                    users.id,
                    users.created_at as customer_since,
                    COUNT(packages.id) as total_orders,
                    SUM(packages.freight_price + packages.customs_duty + packages.storage_fee + packages.delivery_fee) as total_spent,
                    AVG(packages.freight_price + packages.customs_duty + packages.storage_fee + packages.delivery_fee) as avg_order_value,
                    DATEDIFF(NOW(), users.created_at) as days_as_customer,
                    MIN(packages.created_at) as first_order_date,
                    MAX(packages.created_at) as last_order_date
                ")
                ->whereBetween('packages.created_at', $dateRange)
                ->groupBy('users.id', 'users.created_at')
                ->having('total_orders', '>', 0)
                ->get()
                ->map(function ($customer) {
                    $daysAsCustomer = max(1, $customer->days_as_customer);
                    $monthsAsCustomer = $daysAsCustomer / 30;
                    
                    // Calculate estimated CLV based on current behavior
                    $avgMonthlySpend = $customer->total_spent / max(1, $monthsAsCustomer);
                    $estimatedLifespanMonths = 12; // Could be calculated from churn data
                    $estimatedClv = $avgMonthlySpend * $estimatedLifespanMonths;
                    
                    return [
                        'customer_id' => $customer->id,
                        'total_spent' => (float) $customer->total_spent,
                        'total_orders' => $customer->total_orders,
                        'avg_order_value' => round((float) $customer->avg_order_value, 2),
                        'days_as_customer' => $daysAsCustomer,
                        'months_as_customer' => round($monthsAsCustomer, 1),
                        'avg_monthly_spend' => round($avgMonthlySpend, 2),
                        'estimated_clv' => round($estimatedClv, 2),
                        'order_frequency' => round($customer->total_orders / max(1, $monthsAsCustomer), 2),
                    ];
                })
                ->toArray();

            return $clvData;
        });
    }

    /**
     * Get growth rate and period comparison data
     */
    public function getGrowthRateAnalysis()
    {
        $filters = $this->getFilters();
        $cacheKey = $this->analyticsService->cacheKey('growth_rate_analysis', $filters);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            $previousRange = $this->getPreviousDateRange($filters);
            
            // Current period analysis
            $currentPeriod = Package::whereBetween('created_at', $dateRange)
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_revenue,
                    COUNT(DISTINCT user_id) as unique_customers,
                    AVG(freight_price + customs_duty + storage_fee + delivery_fee) as avg_order_value
                ")
                ->first();

            // Previous period analysis
            $previousPeriod = Package::whereBetween('created_at', $previousRange)
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_revenue,
                    COUNT(DISTINCT user_id) as unique_customers,
                    AVG(freight_price + customs_duty + storage_fee + delivery_fee) as avg_order_value
                ")
                ->first();

            // Calculate various growth rates
            $revenueGrowth = $this->calculateGrowthRate(
                $currentPeriod->total_revenue ?? 0,
                $previousPeriod->total_revenue ?? 0
            );
            
            $orderGrowth = $this->calculateGrowthRate(
                $currentPeriod->total_orders ?? 0,
                $previousPeriod->total_orders ?? 0
            );
            
            $customerGrowth = $this->calculateGrowthRate(
                $currentPeriod->unique_customers ?? 0,
                $previousPeriod->unique_customers ?? 0
            );
            
            $aovGrowth = $this->calculateGrowthRate(
                $currentPeriod->avg_order_value ?? 0,
                $previousPeriod->avg_order_value ?? 0
            );

            return [
                'current_period' => [
                    'total_revenue' => (float) ($currentPeriod->total_revenue ?? 0),
                    'total_orders' => $currentPeriod->total_orders ?? 0,
                    'unique_customers' => $currentPeriod->unique_customers ?? 0,
                    'avg_order_value' => round((float) ($currentPeriod->avg_order_value ?? 0), 2),
                ],
                'previous_period' => [
                    'total_revenue' => (float) ($previousPeriod->total_revenue ?? 0),
                    'total_orders' => $previousPeriod->total_orders ?? 0,
                    'unique_customers' => $previousPeriod->unique_customers ?? 0,
                    'avg_order_value' => round((float) ($previousPeriod->avg_order_value ?? 0), 2),
                ],
                'growth_rates' => [
                    'revenue_growth' => $revenueGrowth,
                    'order_growth' => $orderGrowth,
                    'customer_growth' => $customerGrowth,
                    'aov_growth' => $aovGrowth,
                ],
                'period_info' => [
                    'current_start' => $dateRange[0]->format('Y-m-d'),
                    'current_end' => $dateRange[1]->format('Y-m-d'),
                    'previous_start' => $previousRange[0]->format('Y-m-d'),
                    'previous_end' => $previousRange[1]->format('Y-m-d'),
                ],
            ];
        });
    }

    /**
     * Helper method to calculate growth rate percentage
     */
    public function calculateGrowthRate($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get filters array for caching
     */
    protected function getFilters()
    {
        return [
            'date_range' => $this->dateRange,
            'custom_start' => $this->customStartDate,
            'custom_end' => $this->customEndDate,
        ];
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        $days = $filters['date_range'] ?? 30;
        
        if (!empty($filters['custom_start']) && !empty($filters['custom_end'])) {
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
        
        if (!empty($filters['custom_start']) && !empty($filters['custom_end'])) {
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

    /**
     * Get grouping period based on date range
     */
    protected function getGroupByPeriod(array $filters): string
    {
        $days = $filters['date_range'] ?? 30;
        
        if ($days <= 7) {
            return 'day';
        } elseif ($days <= 90) {
            return 'week';
        } else {
            return 'month';
        }
    }

    /**
     * Get SQL date format based on grouping period
     */
    protected function getDateFormat(string $groupBy): string
    {
        switch ($groupBy) {
            case 'day':
                return "DATE(created_at)";
            case 'week':
                return "DATE_FORMAT(created_at, '%Y-%u')";
            case 'month':
                return "DATE_FORMAT(created_at, '%Y-%m')";
            default:
                return "DATE(created_at)";
        }
    }

    /**
     * Get period in months for calculations
     */
    protected function getPeriodInMonths(array $filters): float
    {
        $days = $filters['date_range'] ?? 30;
        
        if (!empty($filters['custom_start']) && !empty($filters['custom_end'])) {
            $start = Carbon::parse($filters['custom_start']);
            $end = Carbon::parse($filters['custom_end']);
            return $start->diffInDays($end) / 30;
        }

        return $days / 30;
    }

    public function render()
    {
        return view('livewire.financial-analytics', [
            'revenueTrends' => $this->getRevenueTrendData(),
            'revenueByService' => $this->getRevenueByServiceType(),
            'revenueBySegment' => $this->getRevenueByCustomerSegment(),
            'financialKPIs' => $this->getFinancialKPIs(),
            'profitMargins' => $this->getProfitMarginAnalysis(),
            'customerCLV' => $this->getCustomerLifetimeValueData(),
            'growthAnalysis' => $this->getGrowthRateAnalysis(),
            'isLoading' => $this->isLoading,
        ]);
    }
}
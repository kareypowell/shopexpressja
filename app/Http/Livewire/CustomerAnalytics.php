<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Package;
use App\Services\DashboardCacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerAnalytics extends Component
{
    public array $filters = [];
    public bool $isLoading = false;
    
    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
        'refreshDashboard' => 'refreshData'
    ];
    
    protected DashboardCacheService $cacheService;

    public function boot(DashboardCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function mount(array $filters = [])
    {
        $this->filters = array_merge([
            'date_range' => '30',
            'custom_start' => null,
            'custom_end' => null,
        ], $filters);
    }

    public function updateFilters(array $filters)
    {
        $this->filters = $filters;
        $this->isLoading = true;
        $this->emit('dataRefreshed');
        $this->isLoading = false;
    }

    public function refreshData()
    {
        $this->isLoading = true;
        $this->emit('dataRefreshed');
        $this->isLoading = false;
    }

    public function render()
    {
        $customerGrowthData = $this->getCustomerGrowthData();
        $customerStatusDistribution = $this->getCustomerStatusDistribution();
        $geographicDistribution = $this->getGeographicDistribution();
        $customerActivityLevels = $this->getCustomerActivityLevels();

        return view('livewire.customer-analytics', [
            'customerGrowthData' => $customerGrowthData,
            'customerStatusDistribution' => $customerStatusDistribution,
            'geographicDistribution' => $geographicDistribution,
            'customerActivityLevels' => $customerActivityLevels,
        ]);
    }

    /**
     * Get customer growth data aggregation for line chart
     */
    public function getCustomerGrowthData(): array
    {
        $cacheKey = $this->cacheKey('customer_growth');
        
        return $this->cacheService->remember($cacheKey, 600, function () {
            $dateRange = $this->getDateRange();
            
            // Get daily customer registrations
            $growthData = User::customers()
                ->whereBetween('created_at', $dateRange)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as new_customers')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Calculate cumulative growth
            $cumulativeTotal = User::customers()
                ->where('created_at', '<', $dateRange[0])
                ->count();

            $chartData = [];
            $labels = [];
            $newCustomers = [];
            $cumulativeCustomers = [];

            foreach ($growthData as $data) {
                $cumulativeTotal += $data->new_customers;
                
                $labels[] = Carbon::parse($data->date)->format('M j');
                $newCustomers[] = $data->new_customers;
                $cumulativeCustomers[] = $cumulativeTotal;
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'New Customers',
                        'data' => $newCustomers,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.4,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Total Customers',
                        'data' => $cumulativeCustomers,
                        'borderColor' => 'rgb(16, 185, 129)',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'tension' => 0.4,
                        'yAxisID' => 'y1',
                    ]
                ],
                'summary' => [
                    'total_new' => array_sum($newCustomers),
                    'current_total' => $cumulativeTotal,
                    'average_daily' => count($newCustomers) > 0 ? round(array_sum($newCustomers) / count($newCustomers), 1) : 0,
                ]
            ];
        });
    }

    /**
     * Get customer status distribution for doughnut chart
     */
    public function getCustomerStatusDistribution(): array
    {
        $cacheKey = $this->cacheKey('customer_status');
        
        return $this->cacheService->remember($cacheKey, 300, function () {
            $dateRange = $this->getDateRange();
            
            // Get customers created in the date range
            $customersInRange = User::customers()
                ->whereBetween('created_at', $dateRange)
                ->get();

            $activeCount = $customersInRange->where('deleted_at', null)
                ->where('email_verified_at', '!=', null)
                ->count();
            
            $inactiveCount = $customersInRange->where('deleted_at', null)
                ->where('email_verified_at', null)
                ->count();
            
            $suspendedCount = $customersInRange->where('deleted_at', '!=', null)
                ->count();

            $total = $activeCount + $inactiveCount + $suspendedCount;

            return [
                'labels' => ['Active', 'Inactive', 'Suspended'],
                'datasets' => [
                    [
                        'data' => [$activeCount, $inactiveCount, $suspendedCount],
                        'backgroundColor' => [
                            'rgb(34, 197, 94)',   // Green for active
                            'rgb(251, 191, 36)',  // Yellow for inactive
                            'rgb(239, 68, 68)',   // Red for suspended
                        ],
                        'borderWidth' => 2,
                        'borderColor' => '#ffffff',
                    ]
                ],
                'summary' => [
                    'total' => $total,
                    'active' => $activeCount,
                    'inactive' => $inactiveCount,
                    'suspended' => $suspendedCount,
                    'active_percentage' => $total > 0 ? round(($activeCount / $total) * 100, 1) : 0,
                ]
            ];
        });
    }

    /**
     * Get geographic distribution analysis
     */
    public function getGeographicDistribution(): array
    {
        $cacheKey = $this->cacheKey('geographic_distribution');
        
        return $this->cacheService->remember($cacheKey, 600, function () {
            $dateRange = $this->getDateRange();
            
            // Get geographic distribution by parish (most specific location data available)
            $geographicData = User::customers()
                ->whereBetween('users.created_at', $dateRange)
                ->join('profiles', 'users.id', '=', 'profiles.user_id')
                ->whereNotNull('profiles.parish')
                ->where('profiles.parish', '!=', '')
                ->selectRaw('profiles.parish, COUNT(*) as customer_count')
                ->groupBy('profiles.parish')
                ->orderByDesc('customer_count')
                ->limit(10) // Top 10 parishes
                ->get();

            $labels = [];
            $data = [];
            $colors = [
                'rgb(59, 130, 246)', 'rgb(16, 185, 129)', 'rgb(251, 191, 36)',
                'rgb(239, 68, 68)', 'rgb(147, 51, 234)', 'rgb(236, 72, 153)',
                'rgb(14, 165, 233)', 'rgb(34, 197, 94)', 'rgb(245, 158, 11)',
                'rgb(168, 85, 247)'
            ];

            foreach ($geographicData as $index => $location) {
                $labels[] = $location->parish;
                $data[] = $location->customer_count;
            }

            // Get country distribution as well
            $countryData = User::customers()
                ->whereBetween('users.created_at', $dateRange)
                ->join('profiles', 'users.id', '=', 'profiles.user_id')
                ->whereNotNull('profiles.country')
                ->where('profiles.country', '!=', '')
                ->selectRaw('profiles.country, COUNT(*) as customer_count')
                ->groupBy('profiles.country')
                ->orderByDesc('customer_count')
                ->get();

            return [
                'parish_distribution' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Customers by Parish',
                            'data' => $data,
                            'backgroundColor' => array_slice($colors, 0, count($data)),
                            'borderWidth' => 1,
                            'borderColor' => '#ffffff',
                        ]
                    ]
                ],
                'country_summary' => $countryData->map(function ($item) {
                    return [
                        'country' => $item->country,
                        'count' => $item->customer_count,
                    ];
                })->toArray(),
                'total_with_location' => array_sum($data),
            ];
        });
    }

    /**
     * Get customer activity levels analysis
     */
    public function getCustomerActivityLevels(): array
    {
        $cacheKey = $this->cacheKey('customer_activity');
        
        return $this->cacheService->remember($cacheKey, 300, function () {
            $dateRange = $this->getDateRange();
            
            // Get customers and their package counts in the date range
            $customerActivity = User::customers()
                ->whereBetween('users.created_at', $dateRange)
                ->withCount(['packages as packages_in_period' => function ($query) use ($dateRange) {
                    $query->whereBetween('created_at', $dateRange);
                }])
                ->get();

            // Categorize customers by activity level
            $highActivity = $customerActivity->where('packages_in_period', '>=', 5)->count();
            $mediumActivity = $customerActivity->where('packages_in_period', '>=', 2)
                ->where('packages_in_period', '<', 5)->count();
            $lowActivity = $customerActivity->where('packages_in_period', '>=', 1)
                ->where('packages_in_period', '<', 2)->count();
            $noActivity = $customerActivity->where('packages_in_period', 0)->count();

            // Calculate revenue by activity level
            $revenueByActivity = [
                'high' => 0,
                'medium' => 0,
                'low' => 0,
                'none' => 0,
            ];

            foreach ($customerActivity as $customer) {
                $revenue = Package::where('user_id', $customer->id)
                    ->whereBetween('created_at', $dateRange)
                    ->selectRaw('SUM(freight_price + clearance_fee + storage_fee + delivery_fee) as total')
                    ->value('total') ?? 0;

                if ($customer->packages_in_period >= 5) {
                    $revenueByActivity['high'] += $revenue;
                } elseif ($customer->packages_in_period >= 2) {
                    $revenueByActivity['medium'] += $revenue;
                } elseif ($customer->packages_in_period >= 1) {
                    $revenueByActivity['low'] += $revenue;
                } else {
                    $revenueByActivity['none'] += $revenue;
                }
            }

            return [
                'activity_distribution' => [
                    'labels' => ['High Activity (5+ packages)', 'Medium Activity (2-4 packages)', 'Low Activity (1 package)', 'No Activity'],
                    'datasets' => [
                        [
                            'label' => 'Number of Customers',
                            'data' => [$highActivity, $mediumActivity, $lowActivity, $noActivity],
                            'backgroundColor' => [
                                'rgb(34, 197, 94)',   // Green for high
                                'rgb(59, 130, 246)',  // Blue for medium
                                'rgb(251, 191, 36)',  // Yellow for low
                                'rgb(156, 163, 175)', // Gray for none
                            ],
                            'borderWidth' => 1,
                            'borderColor' => '#ffffff',
                        ]
                    ]
                ],
                'revenue_by_activity' => [
                    'labels' => ['High Activity', 'Medium Activity', 'Low Activity', 'No Activity'],
                    'datasets' => [
                        [
                            'label' => 'Revenue ($)',
                            'data' => [
                                round($revenueByActivity['high'], 2),
                                round($revenueByActivity['medium'], 2),
                                round($revenueByActivity['low'], 2),
                                round($revenueByActivity['none'], 2),
                            ],
                            'backgroundColor' => [
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(251, 191, 36, 0.8)',
                                'rgba(156, 163, 175, 0.8)',
                            ],
                            'borderColor' => [
                                'rgb(34, 197, 94)',
                                'rgb(59, 130, 246)',
                                'rgb(251, 191, 36)',
                                'rgb(156, 163, 175)',
                            ],
                            'borderWidth' => 1,
                        ]
                    ]
                ],
                'summary' => [
                    'total_customers' => $customerActivity->count(),
                    'active_customers' => $highActivity + $mediumActivity + $lowActivity,
                    'average_packages_per_customer' => $customerActivity->count() > 0 
                        ? round($customerActivity->sum('packages_in_period') / $customerActivity->count(), 1) 
                        : 0,
                    'total_revenue' => round(array_sum($revenueByActivity), 2),
                ]
            ];
        });
    }

    /**
     * Generate cache key for customer analytics data
     */
    protected function cacheKey(string $type): string
    {
        $filterHash = md5(serialize($this->filters));
        return "customer_analytics.{$type}.{$filterHash}";
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(): array
    {
        $days = $this->filters['date_range'] ?? 30;
        
        if (!empty($this->filters['custom_start']) && !empty($this->filters['custom_end'])) {
            return [
                Carbon::parse($this->filters['custom_start'])->startOfDay(),
                Carbon::parse($this->filters['custom_end'])->endOfDay(),
            ];
        }

        return [
            Carbon::now()->subDays($days)->startOfDay(),
            Carbon::now()->endOfDay(),
        ];
    }

}
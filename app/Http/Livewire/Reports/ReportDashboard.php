<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Services\BusinessReportService;
use App\Services\ReportDataService;
use App\Services\ReportCacheService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportDashboard extends Component
{
    // Report type selection
    public string $activeReportType = 'sales_collections';
    public ?string $reportType = null;
    public ?string $reportTitle = null;
    public array $availableReports = [];
    
    // Filter state management
    public array $activeFilters = [];
    public bool $filtersApplied = false;
    
    // Dashboard state
    public bool $isLoading = false;
    public bool $isRefreshing = false;
    public ?string $error = null;
    public array $reportData = [];
    
    // Chart data
    public array $chartData = [];
    public bool $chartsLoaded = false;
    
    // Real-time updates
    public string $lastUpdated = '';
    public bool $autoRefresh = false;
    public int $refreshInterval = 300; // 5 minutes
    
    // Layout configuration
    public array $dashboardLayout = [
        'filters' => ['enabled' => true, 'order' => 0],
        'summary_cards' => ['enabled' => true, 'order' => 1],
        'main_chart' => ['enabled' => true, 'order' => 2],
        'data_table' => ['enabled' => true, 'order' => 3],
        'export_controls' => ['enabled' => true, 'order' => 4],
    ];
    
    // Services
    protected BusinessReportService $businessReportService;
    protected ReportDataService $reportDataService;
    protected ReportCacheService $cacheService;

    protected $listeners = [
        'filtersUpdated' => 'handleFiltersUpdated',
        'reportTypeChanged' => 'handleReportTypeChanged',
        'refreshRequested' => 'refreshReport',
        'exportRequested' => 'handleExportRequest',
        'chartDataRequested' => 'loadChartData',
    ];

    public function boot(
        BusinessReportService $businessReportService,
        ReportDataService $reportDataService,
        ReportCacheService $cacheService
    ) {
        $this->businessReportService = $businessReportService;
        $this->reportDataService = $reportDataService;
        $this->cacheService = $cacheService;
    }

    public function mount(?string $reportType = null, ?string $reportTitle = null)
    {
        try {
            // Set report type from parameter if provided
            if ($reportType) {
                $this->reportType = $reportType;
                $this->activeReportType = $this->mapReportType($reportType);
            }
            
            // Set report title
            $this->reportTitle = $reportTitle;
            
            $this->initializeReportTypes();
            $this->loadSavedState();
            $this->loadInitialData();
            $this->updateLastRefreshTime();
        } catch (\Exception $e) {
            Log::error('ReportDashboard mount error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error = 'Failed to initialize report dashboard. Please refresh the page.';
        }
    }

    /**
     * Map external report type to internal report type
     */
    protected function mapReportType(string $reportType): string
    {
        $mapping = [
            'sales' => 'sales_collections',
            'manifests' => 'manifest_performance',
            'customers' => 'customer_analytics',
            'financial' => 'financial_summary',
        ];
        
        return $mapping[$reportType] ?? 'sales_collections';
    }

    /**
     * Initialize available report types based on user permissions
     */
    protected function initializeReportTypes(): void
    {
        $user = Auth::user();
        $this->availableReports = [];

        // Sales & Collections Report
        if ($user->can('report.viewSalesReports')) {
            $this->availableReports['sales_collections'] = [
                'name' => 'Sales & Collections',
                'description' => 'Revenue analysis and outstanding receivables',
                'icon' => 'currency-dollar',
                'color' => 'blue'
            ];
        }

        // Manifest Performance Report
        if ($user->can('report.viewManifestReports')) {
            $this->availableReports['manifest_performance'] = [
                'name' => 'Manifest Performance',
                'description' => 'Shipping efficiency and operational metrics',
                'icon' => 'truck',
                'color' => 'green'
            ];
        }

        // Customer Analytics Report
        if ($user->can('report.viewCustomerReports')) {
            $this->availableReports['customer_analytics'] = [
                'name' => 'Customer Analytics',
                'description' => 'Customer behavior and account analysis',
                'icon' => 'users',
                'color' => 'purple'
            ];
        }

        // Financial Summary Report
        if ($user->can('report.viewSalesReports')) {
            $this->availableReports['financial_summary'] = [
                'name' => 'Financial Summary',
                'description' => 'Comprehensive financial overview',
                'icon' => 'chart-bar',
                'color' => 'yellow'
            ];
        }

        // Set default report type if current one is not available
        if (!isset($this->availableReports[$this->activeReportType])) {
            $this->activeReportType = array_key_first($this->availableReports) ?? 'sales_collections';
        }
    }

    /**
     * Load saved dashboard state from session
     */
    protected function loadSavedState(): void
    {
        $savedState = Session::get('report_dashboard_state', []);
        
        $this->activeReportType = $savedState['active_report_type'] ?? $this->activeReportType;
        $this->activeFilters = $savedState['active_filters'] ?? [];
        $this->dashboardLayout = array_merge($this->dashboardLayout, $savedState['layout'] ?? []);
        $this->autoRefresh = $savedState['auto_refresh'] ?? false;
    }

    /**
     * Save current dashboard state to session
     */
    protected function saveDashboardState(): void
    {
        Session::put('report_dashboard_state', [
            'active_report_type' => $this->activeReportType,
            'active_filters' => $this->activeFilters,
            'layout' => $this->dashboardLayout,
            'auto_refresh' => $this->autoRefresh,
            'last_saved' => now()->toISOString(),
        ]);
    }

    /**
     * Load initial report data
     */
    protected function loadInitialData(): void
    {
        if (empty($this->availableReports)) {
            $this->error = 'No reports available. Please contact your administrator.';
            return;
        }

        $this->loadReportData();
    }

    /**
     * Handle filter updates from ReportFilters component
     */
    public function handleFiltersUpdated(array $filters): void
    {
        try {
            $this->isLoading = true;
            $this->activeFilters = $filters;
            $this->filtersApplied = !empty(array_filter($filters));
            
            $this->loadReportData();
            $this->saveDashboardState();
            
            $this->isLoading = false;
        } catch (\Exception $e) {
            Log::error('ReportDashboard filter update error: ' . $e->getMessage());
            $this->error = 'Failed to update filters. Please try again.';
            $this->isLoading = false;
        }
    }

    /**
     * Handle report type change
     */
    public function handleReportTypeChanged(string $reportType): void
    {
        if (!isset($this->availableReports[$reportType])) {
            return;
        }

        try {
            $this->isLoading = true;
            $this->activeReportType = $reportType;
            $this->error = null;
            
            // Clear previous report data
            $this->reportData = [];
            $this->chartData = [];
            $this->chartsLoaded = false;
            
            $this->loadReportData();
            $this->saveDashboardState();
            
            $this->isLoading = false;
            
            $this->dispatchBrowserEvent('toastr:info', [
                'message' => 'Switched to ' . $this->availableReports[$reportType]['name']
            ]);
        } catch (\Exception $e) {
            Log::error('ReportDashboard report type change error: ' . $e->getMessage());
            $this->error = 'Failed to switch report type. Please try again.';
            $this->isLoading = false;
        }
    }

    /**
     * Load report data based on current type and filters
     */
    protected function loadReportData(): void
    {
        try {
            // Process filters to convert date_range to date_from/date_to
            $processedFilters = $this->processFilters($this->activeFilters);
            
            switch ($this->activeReportType) {
                case 'sales_collections':
                    $this->reportData = $this->businessReportService->generateSalesCollectionsReport($processedFilters);
                    break;
                case 'manifest_performance':
                    $this->reportData = $this->businessReportService->generateManifestPerformanceReport($processedFilters);
                    break;
                case 'customer_analytics':
                    $this->reportData = $this->businessReportService->generateCustomerAnalyticsReport($processedFilters);
                    break;
                case 'financial_summary':
                    $this->reportData = $this->businessReportService->generateFinancialSummaryReport($processedFilters);
                    break;
                default:
                    $this->reportData = [];
            }
            
            $this->updateLastRefreshTime();
            $this->error = null;
        } catch (\Exception $e) {
            Log::error('ReportDashboard load data error: ' . $e->getMessage());
            $this->error = 'Failed to load report data. Please try again.';
            $this->reportData = [];
        }
    }

    /**
     * Process filters to convert UI filter format to service format
     */
    protected function processFilters(array $filters): array
    {
        $processed = $filters;
        
        // Convert date_range to date_from and date_to
        if (isset($filters['date_range'])) {
            $dateRange = $filters['date_range'];
            
            if ($dateRange === 'custom' && isset($filters['custom_start']) && isset($filters['custom_end'])) {
                // Use custom date range
                $processed['date_from'] = Carbon::parse($filters['custom_start']);
                $processed['date_to'] = Carbon::parse($filters['custom_end']);
            } else {
                // Use predefined date range
                $days = (int) $dateRange;
                $processed['date_from'] = Carbon::now()->subDays($days);
                // Include future dates by extending the end date
                $processed['date_to'] = Carbon::now()->addDays(30);
            }
        } else {
            // Default to last 30 days if no date range specified
            $processed['date_from'] = Carbon::now()->subDays(30);
            // Include future dates by extending the end date
            $processed['date_to'] = Carbon::now()->addDays(30);
        }
        
        // Convert office array to office_ids
        if (isset($filters['offices']) && is_array($filters['offices'])) {
            $processed['office_ids'] = $filters['offices'];
        }
        
        // Convert manifest_types to manifest_type filter
        if (isset($filters['manifest_types']) && is_array($filters['manifest_types']) && !empty($filters['manifest_types'])) {
            // If only one type selected, use it as manifest_type
            if (count($filters['manifest_types']) === 1) {
                $processed['manifest_type'] = $filters['manifest_types'][0];
            }
            // If multiple types selected, let the service handle the array
        }
        
        return $processed;
    }

    /**
     * Load chart data for visualizations
     */
    public function loadChartData(): void
    {
        try {
            if (empty($this->reportData)) {
                return;
            }

            switch ($this->activeReportType) {
                case 'sales_collections':
                    $this->chartData = $this->prepareCollectionsChartData();
                    break;
                case 'manifest_performance':
                    $this->chartData = $this->prepareManifestChartData();
                    break;
                case 'customer_analytics':
                    $this->chartData = $this->prepareCustomerChartData();
                    break;
                case 'financial_summary':
                    $this->chartData = $this->prepareFinancialChartData();
                    break;
            }
            
            $this->chartsLoaded = true;
            $this->emit('chartDataLoaded', $this->chartData);
        } catch (\Exception $e) {
            Log::error('ReportDashboard chart data error: ' . $e->getMessage());
            $this->chartData = [];
            $this->chartsLoaded = false;
        }
    }

    /**
     * Refresh current report
     */
    public function refreshReport(): void
    {
        try {
            $this->isRefreshing = true;
            $this->error = null;
            
            // Clear cache for current report
            $this->clearReportCache();
            
            // Reload data
            $this->loadReportData();
            
            // Reload charts if they were loaded
            if ($this->chartsLoaded) {
                $this->loadChartData();
            }
            
            $this->isRefreshing = false;
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Report refreshed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('ReportDashboard refresh error: ' . $e->getMessage());
            $this->error = 'Failed to refresh report. Please try again.';
            $this->isRefreshing = false;
        }
    }

    /**
     * Handle export request
     */
    public function handleExportRequest(array $exportConfig): void
    {
        $this->emit('exportReport', [
            'report_type' => $this->activeReportType,
            'filters' => $this->activeFilters,
            'data' => $this->reportData,
            'config' => $exportConfig
        ]);
    }

    /**
     * Toggle auto-refresh
     */
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
        $this->saveDashboardState();
        
        if ($this->autoRefresh) {
            $this->dispatchBrowserEvent('startAutoRefresh', [
                'interval' => $this->refreshInterval * 1000 // Convert to milliseconds
            ]);
        } else {
            $this->dispatchBrowserEvent('stopAutoRefresh');
        }
    }

    /**
     * Change report type
     */
    public function changeReportType(string $reportType): void
    {
        $this->handleReportTypeChanged($reportType);
    }

    /**
     * Get current report configuration
     */
    public function getCurrentReportConfig(): array
    {
        return $this->availableReports[$this->activeReportType] ?? [];
    }

    /**
     * Get summary statistics for current report
     */
    public function getSummaryStats(): array
    {
        if (empty($this->reportData)) {
            return [];
        }

        switch ($this->activeReportType) {
            case 'sales_collections':
                return $this->formatSalesSummaryStats();
            case 'manifest_performance':
                return $this->formatManifestSummaryStats();
            case 'customer_analytics':
                return $this->formatCustomerSummaryStats();
            case 'financial_summary':
                return $this->formatFinancialSummaryStats();
            default:
                return [];
        }
    }

    /**
     * Format sales collections summary statistics
     */
    protected function formatSalesSummaryStats(): array
    {
        $summary = $this->reportData['summary'] ?? [];
        
        return [
            [
                'label' => 'Total Revenue',
                'value' => '$' . number_format($summary['total_revenue_owed'] ?? 0, 2),
                'color' => 'blue',
                'change' => null
            ],
            [
                'label' => 'Collected',
                'value' => '$' . number_format($summary['total_revenue_collected'] ?? 0, 2),
                'color' => 'green',
                'change' => null
            ],
            [
                'label' => 'Outstanding',
                'value' => '$' . number_format($summary['total_outstanding'] ?? 0, 2),
                'color' => 'red',
                'change' => null
            ],
            [
                'label' => 'Collection Rate',
                'value' => round($summary['overall_collection_rate'] ?? 0, 1) . '%',
                'color' => 'purple',
                'change' => null
            ]
        ];
    }

    /**
     * Format manifest performance summary statistics
     */
    protected function formatManifestSummaryStats(): array
    {
        $manifests = $this->reportData['manifests'] ?? [];
        $totalManifests = count($manifests);
        $totalPackages = collect($manifests)->sum('package_count');
        $deliveredPackages = collect($manifests)->sum('delivered_count');
        $deliveryRate = $totalPackages > 0 ? ($deliveredPackages / $totalPackages) * 100 : 0;
        
        return [
            [
                'label' => 'Total Manifests',
                'value' => number_format($totalManifests),
                'color' => 'blue',
                'change' => null
            ],
            [
                'label' => 'Total Packages',
                'value' => number_format($totalPackages),
                'color' => 'green',
                'change' => null
            ],
            [
                'label' => 'Delivered',
                'value' => number_format($deliveredPackages),
                'color' => 'purple',
                'change' => null
            ],
            [
                'label' => 'Delivery Rate',
                'value' => round($deliveryRate, 1) . '%',
                'color' => 'yellow',
                'change' => null
            ]
        ];
    }

    /**
     * Format customer analytics summary statistics
     */
    protected function formatCustomerSummaryStats(): array
    {
        $customers = $this->reportData['customers'] ?? [];
        $totalCustomers = count($customers);
        $totalSpent = collect($customers)->sum('total_spent');
        $avgSpent = $totalCustomers > 0 ? $totalSpent / $totalCustomers : 0;
        $customersWithDebt = collect($customers)->where('account_balance', '<', 0)->count();
        
        return [
            [
                'label' => 'Total Customers',
                'value' => number_format($totalCustomers),
                'color' => 'blue',
                'change' => null
            ],
            [
                'label' => 'Total Spent',
                'value' => '$' . number_format($totalSpent, 2),
                'color' => 'green',
                'change' => null
            ],
            [
                'label' => 'Average per Customer',
                'value' => '$' . number_format($avgSpent, 2),
                'color' => 'purple',
                'change' => null
            ],
            [
                'label' => 'With Outstanding',
                'value' => number_format($customersWithDebt),
                'color' => 'red',
                'change' => null
            ]
        ];
    }

    /**
     * Format financial summary statistics
     */
    protected function formatFinancialSummaryStats(): array
    {
        $revenue = $this->reportData['revenue_breakdown'] ?? [];
        $collections = $this->reportData['collections'] ?? [];
        $outstanding = $this->reportData['outstanding'] ?? [];
        
        return [
            [
                'label' => 'Total Revenue',
                'value' => '$' . number_format($revenue['total_revenue'] ?? 0, 2),
                'color' => 'blue',
                'change' => null
            ],
            [
                'label' => 'Collections',
                'value' => '$' . number_format($collections['total_collected'] ?? 0, 2),
                'color' => 'green',
                'change' => null
            ],
            [
                'label' => 'Outstanding',
                'value' => '$' . number_format($outstanding['total_outstanding'] ?? 0, 2),
                'color' => 'red',
                'change' => null
            ],
            [
                'label' => 'Packages',
                'value' => number_format($revenue['package_count'] ?? 0),
                'color' => 'purple',
                'change' => null
            ]
        ];
    }

    /**
     * Get table data for current report
     */
    public function getTableData(): array
    {
        if (empty($this->reportData)) {
            return [];
        }

        // Format data based on report type for the data table
        switch ($this->activeReportType) {
            case 'sales_collections':
                return $this->formatSalesTableData();
            case 'manifest_performance':
                return $this->formatManifestTableData();
            case 'customer_analytics':
                return $this->formatCustomerTableData();
            case 'financial_summary':
                return $this->formatFinancialTableData();
            default:
                return [];
        }
    }

    /**
     * Format sales collections data for table display
     */
    protected function formatSalesTableData(): array
    {
        if (!isset($this->reportData['manifests'])) {
            return [];
        }

        return collect($this->reportData['manifests'])->map(function ($manifest) {
            return [
                'id' => $manifest['manifest_id'],
                'manifest_number' => $manifest['manifest_name'],
                'manifest_type' => ucfirst($manifest['manifest_type']),
                'office_name' => 'N/A', // Will be enhanced later with office data
                'total_packages' => $manifest['package_count'],
                'total_owed' => $manifest['total_owed'],
                'total_collected' => $manifest['total_collected'],
                'outstanding_balance' => $manifest['outstanding_balance'],
                'collection_rate' => round($manifest['collection_rate'], 1),
                'created_at' => $manifest['shipment_date'],
            ];
        })->toArray();
    }

    /**
     * Format manifest performance data for table display
     */
    protected function formatManifestTableData(): array
    {
        if (!isset($this->reportData['manifests'])) {
            return [];
        }

        return collect($this->reportData['manifests'])->map(function ($manifest) {
            return [
                'id' => $manifest['manifest_id'],
                'manifest_number' => $manifest['manifest_name'],
                'manifest_type' => ucfirst($manifest['manifest_type']),
                'office_name' => 'N/A',
                'package_count' => $manifest['package_count'],
                'total_weight' => $manifest['total_weight'] ?? 0,
                'total_volume' => $manifest['total_volume'] ?? 0,
                'processing_time' => $manifest['average_processing_time_days'] ? 
                    round($manifest['average_processing_time_days']) . ' days' : 'N/A',
                'efficiency_score' => round($manifest['completion_rate'], 1) . '%',
                'status' => $manifest['delivered_count'] == $manifest['package_count'] ? 'Completed' : 'In Progress',
                'created_at' => $manifest['shipment_date'],
            ];
        })->toArray();
    }

    /**
     * Format customer analytics data for table display
     */
    protected function formatCustomerTableData(): array
    {
        if (!isset($this->reportData['customers'])) {
            return [];
        }

        return collect($this->reportData['customers'])->map(function ($customer) {
            return [
                'id' => $customer['customer_id'],
                'customer_name' => $customer['customer_name'],
                'email' => $customer['customer_email'],
                'total_packages' => $customer['package_count'],
                'account_balance' => $customer['account_balance'],
                'total_spent' => $customer['total_spent'],
                'last_activity' => $customer['last_package_date'],
                'status' => $customer['account_balance'] < 0 ? 'Outstanding' : 'Current',
            ];
        })->toArray();
    }

    /**
     * Format financial summary data for table display
     */
    protected function formatFinancialTableData(): array
    {
        if (!isset($this->reportData['revenue_breakdown'])) {
            return [];
        }

        $breakdown = $this->reportData['revenue_breakdown'];
        return [
            [
                'id' => 1,
                'service_type' => 'Freight Charges',
                'revenue' => $breakdown['freight_revenue'],
                'percentage' => $breakdown['total_revenue'] > 0 ? 
                    round(($breakdown['freight_revenue'] / $breakdown['total_revenue']) * 100, 1) . '%' : '0%'
            ],
            [
                'id' => 2,
                'service_type' => 'Customs Duties',
                'revenue' => $breakdown['customs_revenue'],
                'percentage' => $breakdown['total_revenue'] > 0 ? 
                    round(($breakdown['customs_revenue'] / $breakdown['total_revenue']) * 100, 1) . '%' : '0%'
            ],
            [
                'id' => 3,
                'service_type' => 'Storage Fees',
                'revenue' => $breakdown['storage_revenue'],
                'percentage' => $breakdown['total_revenue'] > 0 ? 
                    round(($breakdown['storage_revenue'] / $breakdown['total_revenue']) * 100, 1) . '%' : '0%'
            ],
            [
                'id' => 4,
                'service_type' => 'Delivery Fees',
                'revenue' => $breakdown['delivery_revenue'],
                'percentage' => $breakdown['total_revenue'] > 0 ? 
                    round(($breakdown['delivery_revenue'] / $breakdown['total_revenue']) * 100, 1) . '%' : '0%'
            ]
        ];
    }

    /**
     * Clear report cache
     */
    protected function clearReportCache(): void
    {
        $cacheKey = "report_{$this->activeReportType}_" . md5(serialize($this->activeFilters));
        $this->cacheService->forget($cacheKey);
    }

    /**
     * Update last refresh time
     */
    protected function updateLastRefreshTime(): void
    {
        $this->lastUpdated = Carbon::now()->format('M j, Y g:i A');
    }

    /**
     * Prepare chart data for different report types
     */
    protected function prepareCollectionsChartData(): array
    {
        if (!isset($this->reportData['collections']['daily_collections'])) {
            return [
                'type' => 'collections',
                'data' => [],
                'xAxisLabel' => 'Date',
                'yAxisLabel' => 'Amount ($)'
            ];
        }

        $dailyData = $this->reportData['collections']['daily_collections'];
        
        return [
            'type' => 'collections',
            'data' => [
                'labels' => collect($dailyData)->pluck('date')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Daily Collections',
                        'data' => collect($dailyData)->pluck('total_amount')->toArray(),
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.1
                    ]
                ]
            ],
            'xAxisLabel' => 'Date',
            'yAxisLabel' => 'Amount ($)'
        ];
    }

    protected function prepareManifestChartData(): array
    {
        return [
            'type' => 'manifest_performance',
            'data' => $this->reportData['chart_data'] ?? [],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ]
        ];
    }

    protected function prepareCustomerChartData(): array
    {
        return [
            'type' => 'customer_analytics',
            'data' => $this->reportData['chart_data'] ?? [],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ]
        ];
    }

    protected function prepareFinancialChartData(): array
    {
        return [
            'type' => 'financial_summary',
            'data' => $this->reportData['chart_data'] ?? [],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ]
        ];
    }

    /**
     * Check if component should be displayed
     */
    public function shouldShowComponent(string $componentName): bool
    {
        return $this->dashboardLayout[$componentName]['enabled'] ?? false;
    }

    /**
     * Get component order
     */
    public function getComponentOrder(string $componentName): int
    {
        return $this->dashboardLayout[$componentName]['order'] ?? 999;
    }

    /**
     * Get sorted components for rendering
     */
    public function getSortedComponents(): array
    {
        $components = array_filter($this->dashboardLayout, fn($config) => $config['enabled'] ?? false);
        
        uasort($components, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
        
        return array_keys($components);
    }

    /**
     * Get the current breadcrumb title
     */
    public function getCurrentBreadcrumbTitle(): string
    {
        if ($this->reportTitle) {
            return $this->reportTitle;
        }
        
        $currentReport = $this->getCurrentReportConfig();
        if (!empty($currentReport['name'])) {
            return $currentReport['name'] . ' Report';
        }
        
        return 'Report Dashboard';
    }

    public function render()
    {
        try {
            return view('livewire.reports.report-dashboard', [
                'currentReport' => $this->getCurrentReportConfig(),
                'summaryStats' => $this->getSummaryStats(),
                'tableData' => $this->getTableData(),
                'sortedComponents' => $this->getSortedComponents(),
                'user' => Auth::user(),
            ]);
        } catch (\Exception $e) {
            Log::error('ReportDashboard render error: ' . $e->getMessage());
            return view('livewire.reports.report-dashboard-error', [
                'error' => 'Report dashboard temporarily unavailable. Please refresh the page.'
            ]);
        }
    }
}
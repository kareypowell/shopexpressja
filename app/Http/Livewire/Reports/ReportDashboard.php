<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Services\BusinessReportService;
use App\Services\ReportCacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReportDashboard extends Component
{
    public $reportType = 'sales';
    public $dateRange = '30';
    public $isLoading = false;
    public $error = null;
    public $reportData = [];
    public $lastUpdated;

    protected $listeners = [
        'refreshData' => 'loadData',
        'retryReportGeneration' => 'retryGeneration',
        'showFallbackData' => 'showFallbackData'
    ];

    public function mount($type = 'sales')
    {
        $this->reportType = $type;
        $this->lastUpdated = now()->format('M d, Y H:i A');
        $this->loadData();
    }

    public function updatedReportType()
    {
        $this->loadData();
        $this->dispatchBrowserEvent('chartDataUpdated');
    }

    public function updatedDateRange()
    {
        $this->loadData();
        $this->dispatchBrowserEvent('chartDataUpdated');
    }

    public function refreshData()
    {
        $this->loadData();
        $this->lastUpdated = now()->format('M d, Y H:i A');
    }

    public function loadData()
    {
        $this->isLoading = true;
        $this->error = null;
        
        try {
            $this->isLoading = true;
            $this->error = null;

            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays((int) $this->dateRange);

            $filters = [
                'date_from' => $startDate,
                'date_to' => $endDate
            ];



            // Try to load data with comprehensive error handling
            $businessService = app(BusinessReportService::class);
            
            $result = null;
            switch ($this->reportType) {
                case 'sales':
                    $result = $businessService->generateSalesCollectionsReport($filters);
                    break;
                case 'manifests':
                    $result = $businessService->generateManifestPerformanceReport($filters);
                    break;
                case 'customers':
                    $result = $businessService->generateCustomerAnalyticsReport($filters);
                    break;
                case 'financial':
                    $result = $businessService->generateFinancialSummaryReport($filters);
                    break;
                default:
                    $result = ['success' => true, 'data' => $this->getEmptyData()];
            }
            
            // Handle the result from the service
            if (isset($result['success']) && $result['success']) {
                $this->reportData = $result['data'];
            } elseif (isset($result['success']) && !$result['success']) {
                // Handle error response from service
                $this->handleServiceError($result);
            } else {
                // Handle case where service returns raw data (backward compatibility)
                $this->reportData = $result;
            }

        } catch (\Exception $e) {
            Log::error('Report Dashboard Error: ' . $e->getMessage());
            $this->error = 'Unable to load reports. Please try again.';
            $this->reportData = $this->getEmptyData();
        } finally {
            $this->isLoading = false;
            // Emit event to update charts
            $this->dispatchBrowserEvent('chartDataUpdated');
        }
    }

    private function getEmptyData()
    {
        switch ($this->reportType) {
            case 'sales':
                return [
                    'summary' => [
                        'total_revenue_owed' => 0,
                        'total_revenue_collected' => 0,
                        'total_outstanding' => 0,
                        'overall_collection_rate' => 0
                    ],
                    'manifests' => [],
                    'collections' => ['daily_collections' => []]
                ];
            case 'manifests':
                return [
                    'manifests' => [],
                    'summary' => [
                        'total_manifests' => 0,
                        'total_packages' => 0,
                        'average_processing_time' => 0,
                        'completion_rate' => 0
                    ]
                ];
            case 'customers':
                return [
                    'customers' => [],
                    'summary' => [
                        'total_customers' => 0,
                        'active_customers' => 0,
                        'total_spent' => 0,
                        'average_spent' => 0
                    ]
                ];
            case 'financial':
                return [
                    'revenue_breakdown' => [
                        'total_revenue' => 0,
                        'freight_revenue' => 0,
                        'customs_revenue' => 0,
                        'storage_revenue' => 0,
                        'delivery_revenue' => 0,
                        'package_count' => 0
                    ],
                    'collections' => ['total_collected' => 0],
                    'outstanding' => ['total_outstanding' => 0]
                ];
            default:
                return [
                    'summary' => [],
                    'manifests' => [],
                    'customers' => []
                ];
        }
    }

    public function getSummaryStats()
    {
        $summary = $this->reportData['summary'] ?? [];
        
        switch ($this->reportType) {
            case 'sales':
                return [
                    ['label' => 'Total Revenue', 'value' => '$' . number_format($summary['total_revenue_owed'] ?? 0, 2), 'color' => 'blue'],
                    ['label' => 'Collected', 'value' => '$' . number_format($summary['total_revenue_collected'] ?? 0, 2), 'color' => 'green'],
                    ['label' => 'Outstanding', 'value' => '$' . number_format($summary['total_outstanding'] ?? 0, 2), 'color' => 'red'],
                    ['label' => 'Collection Rate', 'value' => round($summary['overall_collection_rate'] ?? 0, 1) . '%', 'color' => 'purple']
                ];
            case 'manifests':
                $manifests = $this->reportData ?? [];
                $manifestsData = is_array($manifests) && isset($manifests[0]['manifest_name']) ? $manifests : [];
                $totalManifests = count($manifestsData);
                $totalPackages = collect($manifestsData)->sum('package_count');
                $avgProcessing = collect($manifestsData)
                    ->pluck('average_processing_time_days')
                    ->filter()
                    ->avg();
                $completionRate = collect($manifestsData)
                    ->pluck('completion_rate')
                    ->filter()
                    ->avg();
                
                return [
                    ['label' => 'Total Manifests', 'value' => number_format($totalManifests), 'color' => 'blue'],
                    ['label' => 'Total Packages', 'value' => number_format($totalPackages), 'color' => 'green'],
                    ['label' => 'Avg Processing', 'value' => $avgProcessing ? round($avgProcessing, 1) . ' days' : 'N/A', 'color' => 'purple'],
                    ['label' => 'Completion Rate', 'value' => $completionRate ? round($completionRate, 1) . '%' : 'N/A', 'color' => 'yellow']
                ];
            case 'customers':
                $customers = $this->reportData ?? [];
                $customersData = is_array($customers) && isset($customers[0]['customer_name']) ? $customers : [];
                $totalCustomers = count($customersData);
                $totalSpent = collect($customersData)->sum('total_spent');
                $activeCustomers = collect($customersData)->filter(function($customer) {
                    return $customer['package_count'] > 0;
                })->count();
                $withOutstanding = collect($customersData)->filter(function($customer) {
                    return ($customer['account_balance'] ?? 0) < 0 || ($customer['outstanding_balance'] ?? 0) > 0;
                })->count();
                
                return [
                    ['label' => 'Total Customers', 'value' => number_format($totalCustomers), 'color' => 'blue'],
                    ['label' => 'Total Spent', 'value' => '$' . number_format($totalSpent, 2), 'color' => 'green'],
                    ['label' => 'Active Customers', 'value' => number_format($activeCustomers), 'color' => 'purple'],
                    ['label' => 'With Outstanding', 'value' => number_format($withOutstanding), 'color' => 'red']
                ];
            default:
                return [];
        }
    }

    public function getChartData()
    {
        switch ($this->reportType) {
            case 'sales':
                return $this->getSalesChartData();
            case 'manifests':
                return $this->getManifestChartData();
            case 'customers':
                return $this->getCustomerChartData();
            case 'financial':
                return $this->getFinancialChartData();
            default:
                return ['labels' => [], 'datasets' => []];
        }
    }

    private function getSalesChartData()
    {
        $collections = $this->reportData['collections']['daily_collections'] ?? [];
        
        if (empty($collections)) {
            // Fallback to demo data if no real data
            return [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'datasets' => [[
                    'label' => 'Revenue ($)',
                    'data' => [12000, 15000, 18000, 14000, 16000, 19000],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
                ]]
            ];
        }

        $labels = [];
        $data = [];
        
        // Collections is an array of objects with date, total_amount, etc.
        $sortedCollections = collect($collections)
            ->sortBy('date') // Sort by the date field
            ->take(-30); // Last 30 entries
        
        foreach ($sortedCollections as $dayData) {
            $date = $dayData['date'] ?? null;
            $amount = $dayData['total_amount'] ?? 0;
            
            if ($date) {
                $labels[] = Carbon::parse($date)->format('M j');
                $data[] = (float) $amount;
            }
        }

        // If we still don't have data, use fallback
        if (empty($labels)) {
            return [
                'labels' => ['Today'],
                'datasets' => [[
                    'label' => 'Daily Collections ($)',
                    'data' => [array_sum(array_column($collections, 'total_amount'))],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.1
                ]]
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Daily Collections ($)',
                'data' => $data,
                'borderColor' => 'rgb(59, 130, 246)',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'tension' => 0.1
            ]]
        ];
    }

    private function getManifestChartData()
    {
        $manifests = $this->reportData ?? [];
        
        if (empty($manifests)) {
            return [
                'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                'datasets' => [[
                    'label' => 'Manifests',
                    'data' => [25, 32, 28, 35],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)'
                ]]
            ];
        }

        // For manifest performance, the reportData IS the array of manifests
        $manifestsData = is_array($manifests) && isset($manifests[0]['manifest_name']) ? $manifests : [];
        
        if (empty($manifestsData)) {
            return [
                'labels' => ['No Data'],
                'datasets' => [[
                    'label' => 'Manifests Count',
                    'data' => [0],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.1
                ]]
            ];
        }

        // Group manifests by week or month, sorted by date
        $manifestsByPeriod = collect($manifestsData)
            ->filter(function($manifest) {
                return isset($manifest['shipment_date']) && $manifest['shipment_date'];
            })
            ->groupBy(function($manifest) {
                return Carbon::parse($manifest['shipment_date'])->format('M j');
            })
            ->sortKeys();

        if ($manifestsByPeriod->isEmpty()) {
            // If no valid dates, group by manifest type or show individual manifests
            $manifestsByType = collect($manifestsData)
                ->groupBy('manifest_type')
                ->map(function($typeManifests, $type) {
                    return [
                        'label' => ucfirst($type ?: 'Unknown'),
                        'count' => $typeManifests->count(),
                        'packages' => $typeManifests->sum('package_count')
                    ];
                });

            return [
                'labels' => $manifestsByType->pluck('label')->toArray(),
                'datasets' => [[
                    'label' => 'Package Count',
                    'data' => $manifestsByType->pluck('packages')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.1
                ]]
            ];
        }

        $labels = $manifestsByPeriod->keys()->toArray();
        $data = $manifestsByPeriod->map(function($periodManifests) {
            return $periodManifests->sum('package_count'); // Show package count instead of manifest count
        })->values()->toArray();

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Packages Processed',
                'data' => $data,
                'borderColor' => 'rgb(34, 197, 94)',
                'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                'tension' => 0.1
            ]]
        ];
    }

    private function getCustomerChartData()
    {
        $customers = $this->reportData ?? [];
        
        // For customer analytics, the reportData IS the array of customers
        $customersData = is_array($customers) && isset($customers[0]['customer_name']) ? $customers : [];
        
        if (empty($customersData)) {
            return [
                'labels' => ['No Data'],
                'datasets' => [[
                    'label' => 'Customer Count',
                    'data' => [0],
                    'borderColor' => 'rgb(147, 51, 234)',
                    'backgroundColor' => 'rgba(147, 51, 234, 0.1)',
                    'tension' => 0.1
                ]]
            ];
        }

        // Group customers by their total spent ranges
        $spendingRanges = [
            '$0-$100' => 0,
            '$100-$500' => 0,
            '$500-$1000' => 0,
            '$1000-$2500' => 0,
            '$2500+' => 0
        ];

        foreach ($customersData as $customer) {
            $totalSpent = $customer['total_spent'] ?? 0;
            if ($totalSpent <= 100) {
                $spendingRanges['$0-$100']++;
            } elseif ($totalSpent <= 500) {
                $spendingRanges['$100-$500']++;
            } elseif ($totalSpent <= 1000) {
                $spendingRanges['$500-$1000']++;
            } elseif ($totalSpent <= 2500) {
                $spendingRanges['$1000-$2500']++;
            } else {
                $spendingRanges['$2500+']++;
            }
        }

        // Filter out empty ranges for cleaner chart
        $filteredRanges = array_filter($spendingRanges, function($count) {
            return $count > 0;
        });

        if (empty($filteredRanges)) {
            return [
                'labels' => ['No Spending Data'],
                'datasets' => [[
                    'label' => 'Customer Count',
                    'data' => [count($customersData)],
                    'borderColor' => 'rgb(147, 51, 234)',
                    'backgroundColor' => 'rgba(147, 51, 234, 0.1)',
                    'tension' => 0.1
                ]]
            ];
        }

        return [
            'labels' => array_keys($filteredRanges),
            'datasets' => [[
                'label' => 'Customer Count',
                'data' => array_values($filteredRanges),
                'borderColor' => 'rgb(147, 51, 234)',
                'backgroundColor' => 'rgba(147, 51, 234, 0.1)',
                'tension' => 0.1
            ]]
        ];
    }

    private function getFinancialChartData()
    {
        $financialData = $this->reportData ?? [];
        $revenueBreakdown = $financialData['revenue_breakdown'] ?? [];
        
        if (empty($revenueBreakdown)) {
            return [
                'labels' => ['No Data'],
                'datasets' => [[
                    'label' => 'Revenue ($)',
                    'data' => [0],
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.1
                ]]
            ];
        }

        $labels = ['Freight', 'Clearance', 'Storage', 'Delivery'];
        $data = [
            (float) ($revenueBreakdown['freight_revenue'] ?? 0),
            (float) ($revenueBreakdown['clearance_revenue'] ?? 0),
            (float) ($revenueBreakdown['storage_revenue'] ?? 0),
            (float) ($revenueBreakdown['delivery_revenue'] ?? 0)
        ];

        // Filter out zero values for cleaner chart
        $filteredData = [];
        $filteredLabels = [];
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i] > 0) {
                $filteredData[] = $data[$i];
                $filteredLabels[] = $labels[$i];
            }
        }

        // If no revenue data, show collections vs outstanding
        if (empty($filteredData)) {
            $collections = $financialData['collections']['total_collected'] ?? 0;
            $outstanding = $financialData['outstanding']['total_outstanding'] ?? 0;
            
            if ($collections > 0 || $outstanding > 0) {
                return [
                    'labels' => ['Collections', 'Outstanding'],
                    'datasets' => [[
                        'label' => 'Financial Status ($)',
                        'data' => [(float) $collections, (float) $outstanding],
                        'borderColor' => 'rgb(245, 158, 11)',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                        'tension' => 0.1
                    ]]
                ];
            }
            
            return [
                'labels' => ['No Financial Data'],
                'datasets' => [[
                    'label' => 'Revenue ($)',
                    'data' => [0],
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.1
                ]]
            ];
        }

        return [
            'labels' => $filteredLabels,
            'datasets' => [[
                'label' => 'Revenue by Service ($)',
                'data' => $filteredData,
                'borderColor' => 'rgb(245, 158, 11)',
                'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                'tension' => 0.1
            ]]
        ];
    }

    public function goToManifest($manifestId)
    {
        return redirect()->route('admin.manifests.packages', $manifestId);
    }

    public function goToCustomer($customerId)
    {
        return redirect()->route('admin.customers.show', $customerId);
    }

    public function render()
    {
        return view('livewire.reports.report-dashboard');
    }

    /**
     * Handle service error response
     */
    protected function handleServiceError(array $errorResult): void
    {
        // Check if fallback data is available
        if (isset($errorResult['fallback_data']) && !empty($errorResult['fallback_data'])) {
            $this->reportData = $errorResult['fallback_data'];
            
            // Show a warning that we're using cached data
            $this->dispatchBrowserEvent('show-warning', [
                'message' => 'Using cached data due to temporary issue: ' . $errorResult['message']
            ]);
        } else {
            // No fallback data available
            $this->reportData = $this->getEmptyData();
            
            // Emit error event to show error handler
            $this->emit('reportError', $errorResult);
        }
    }

    /**
     * Retry report generation (called from error handler)
     */
    public function retryGeneration()
    {
        $this->loadData();
    }

    public function showFallbackData($fallbackData)
    {
        $this->reportData = $fallbackData;
        $this->dispatchBrowserEvent('show-info', [
            'message' => 'Displaying cached data from previous successful report generation.'
        ]);
    }

    /**
     * Check if data is empty or error state
     */
    public function getIsEmptyDataProperty(): bool
    {
        if (empty($this->reportData)) {
            return true;
        }

        // Check based on report type
        switch ($this->reportType) {
            case 'sales':
                return empty($this->reportData['manifests']) && 
                       ($this->reportData['summary']['total_revenue_owed'] ?? 0) == 0;
            case 'manifests':
                return empty($this->reportData) || 
                       (is_array($this->reportData) && count($this->reportData) == 0);
            case 'customers':
                return empty($this->reportData) || 
                       (is_array($this->reportData) && count($this->reportData) == 0);
            case 'financial':
                return ($this->reportData['revenue_breakdown']['total_revenue'] ?? 0) == 0;
            default:
                return true;
        }
    }

    /**
     * Get user-friendly error message
     */
    public function getErrorMessageProperty(): ?string
    {
        if ($this->error) {
            return $this->error;
        }

        if ($this->isEmptyData && !$this->isLoading) {
            return "No data available for the selected date range. Try selecting a different period or check if there are any packages in the system.";
        }

        return null;
    }


}
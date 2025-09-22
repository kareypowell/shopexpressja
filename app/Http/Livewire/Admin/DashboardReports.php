<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Services\BusinessReportService;
use App\Services\SalesAnalyticsService;
use App\Services\ManifestAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardReports extends Component
{
    public $loading = true;
    public $refreshInterval = 300; // 5 minutes
    public $lastUpdated;
    
    // Report data
    public $totalRevenue = 0;
    public $revenueChange = 0;
    public $outstandingBalance = 0;
    public $outstandingChange = 0;
    public $collectionRate = 0;
    public $collectionRateChange = 0;
    public $activeManifests = 0;
    public $manifestChange = 0;
    public $processingTime = 0;
    public $processingTimeChange = 0;
    public $customerCount = 0;
    public $customerChange = 0;

    protected $businessReportService;
    protected $salesAnalyticsService;
    protected $manifestAnalyticsService;

    public function boot(
        BusinessReportService $businessReportService,
        SalesAnalyticsService $salesAnalyticsService,
        ManifestAnalyticsService $manifestAnalyticsService
    ) {
        $this->businessReportService = $businessReportService;
        $this->salesAnalyticsService = $salesAnalyticsService;
        $this->manifestAnalyticsService = $manifestAnalyticsService;
    }

    public function mount()
    {
        // Only load data if user has permission
        if (auth()->user() && auth()->user()->canAccessAdminPanel()) {
            $this->loadDashboardData();
        } else {
            $this->loading = false;
        }
    }

    public function loadDashboardData()
    {
        $this->loading = true;
        
        try {
            // Use cache to avoid expensive calculations on every page load
            $cacheKey = 'admin_dashboard_reports_' . auth()->id();
            $data = Cache::remember($cacheKey, $this->refreshInterval, function () {
                return $this->fetchReportData();
            });

            $this->totalRevenue = $data['totalRevenue'];
            $this->revenueChange = $data['revenueChange'];
            $this->outstandingBalance = $data['outstandingBalance'];
            $this->outstandingChange = $data['outstandingChange'];
            $this->collectionRate = $data['collectionRate'];
            $this->collectionRateChange = $data['collectionRateChange'];
            $this->activeManifests = $data['activeManifests'];
            $this->manifestChange = $data['manifestChange'];
            $this->processingTime = $data['processingTime'];
            $this->processingTimeChange = $data['processingTimeChange'];
            $this->customerCount = $data['customerCount'];
            $this->customerChange = $data['customerChange'];
            
            $this->lastUpdated = now()->format('M j, Y g:i A');
            
        } catch (\Exception $e) {
            // Log error and show default values
            \Log::error('Dashboard reports loading failed: ' . $e->getMessage());
            $this->setDefaultValues();
        }
        
        $this->loading = false;
    }

    protected function fetchReportData()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Current month filters
        $currentFilters = [
            'date_from' => $currentMonth->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d'),
        ];
        
        // Previous month filters for comparison
        $previousFilters = [
            'date_from' => $previousMonth->format('Y-m-d'),
            'date_to' => $previousMonth->endOfMonth()->format('Y-m-d'),
        ];

        try {
            // Get current month data
            $currentSalesData = $this->businessReportService->generateSalesCollectionsReport($currentFilters);
            $currentManifestData = $this->businessReportService->generateManifestPerformanceReport($currentFilters);
            $currentCustomerData = $this->businessReportService->generateCustomerAnalyticsReport($currentFilters);
            
            // Get previous month data for comparison
            $previousSalesData = $this->businessReportService->generateSalesCollectionsReport($previousFilters);
            $previousManifestData = $this->businessReportService->generateManifestPerformanceReport($previousFilters);
            $previousCustomerData = $this->businessReportService->generateCustomerAnalyticsReport($previousFilters);
        } catch (\Exception $e) {
            \Log::error('Error fetching report data: ' . $e->getMessage());
            // Return default data structure
            return [
                'totalRevenue' => 0,
                'revenueChange' => 0,
                'outstandingBalance' => 0,
                'outstandingChange' => 0,
                'collectionRate' => 0,
                'collectionRateChange' => 0,
                'activeManifests' => 0,
                'manifestChange' => 0,
                'processingTime' => 0,
                'processingTimeChange' => 0,
                'customerCount' => 0,
                'customerChange' => 0,
            ];
        }

        return [
            'totalRevenue' => $currentSalesData['summary']['total_collected'] ?? 0,
            'revenueChange' => $this->calculatePercentageChange(
                $previousSalesData['summary']['total_collected'] ?? 0,
                $currentSalesData['summary']['total_collected'] ?? 0
            ),
            'outstandingBalance' => $currentSalesData['summary']['total_outstanding'] ?? 0,
            'outstandingChange' => $this->calculatePercentageChange(
                $previousSalesData['summary']['total_outstanding'] ?? 0,
                $currentSalesData['summary']['total_outstanding'] ?? 0
            ),
            'collectionRate' => $currentSalesData['summary']['collection_rate'] ?? 0,
            'collectionRateChange' => $this->calculatePercentageChange(
                $previousSalesData['summary']['collection_rate'] ?? 0,
                $currentSalesData['summary']['collection_rate'] ?? 0
            ),
            'activeManifests' => $currentManifestData['summary']['total_manifests'] ?? 0,
            'manifestChange' => $this->calculatePercentageChange(
                $previousManifestData['summary']['total_manifests'] ?? 0,
                $currentManifestData['summary']['total_manifests'] ?? 0
            ),
            'processingTime' => $currentManifestData['summary']['avg_processing_time'] ?? 0,
            'processingTimeChange' => $this->calculatePercentageChange(
                $previousManifestData['summary']['avg_processing_time'] ?? 0,
                $currentManifestData['summary']['avg_processing_time'] ?? 0
            ),
            'customerCount' => $currentCustomerData['summary']['active_customers'] ?? 0,
            'customerChange' => $this->calculatePercentageChange(
                $previousCustomerData['summary']['active_customers'] ?? 0,
                $currentCustomerData['summary']['active_customers'] ?? 0
            ),
        ];
    }

    protected function calculatePercentageChange($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function setDefaultValues()
    {
        $this->totalRevenue = 0;
        $this->revenueChange = 0;
        $this->outstandingBalance = 0;
        $this->outstandingChange = 0;
        $this->collectionRate = 0;
        $this->collectionRateChange = 0;
        $this->activeManifests = 0;
        $this->manifestChange = 0;
        $this->processingTime = 0;
        $this->processingTimeChange = 0;
        $this->customerCount = 0;
        $this->customerChange = 0;
    }

    public function refreshData()
    {
        // Clear cache and reload data
        $cacheKey = 'admin_dashboard_reports_' . auth()->id();
        Cache::forget($cacheKey);
        $this->loadDashboardData();
        
        $this->emit('dataRefreshed');
    }

    public function render()
    {
        return view('livewire.admin.dashboard-reports');
    }
}
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ReportCacheService;
use App\Services\BusinessReportService;
use App\Services\ReportDataService;
use App\Services\ManifestAnalyticsService;
use App\Services\SalesAnalyticsService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WarmReportCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $cacheTypes;
    protected array $filters;

    /**
     * Create a new job instance.
     */
    public function __construct(array $cacheTypes = ['all'], array $filters = [])
    {
        $this->cacheTypes = $cacheTypes;
        $this->filters = $filters;
        
        // Set job properties
        $this->onQueue('reports');
        $this->timeout = 300; // 5 minutes timeout
    }

    /**
     * Execute the job.
     */
    public function handle(
        ReportCacheService $cacheService,
        BusinessReportService $businessReportService,
        ReportDataService $reportDataService,
        ManifestAnalyticsService $manifestAnalyticsService,
        SalesAnalyticsService $salesAnalyticsService
    ): void {
        Log::info('Starting report cache warming', [
            'cache_types' => $this->cacheTypes,
            'filters' => $this->filters
        ]);

        $startTime = microtime(true);
        $warmedCaches = [];

        try {
            // Determine which caches to warm
            $typesToWarm = in_array('all', $this->cacheTypes) ? 
                ['sales', 'manifest', 'customer', 'financial', 'dashboard'] : 
                $this->cacheTypes;

            foreach ($typesToWarm as $type) {
                try {
                    $this->warmCacheType($type, $cacheService, $businessReportService, $reportDataService, $manifestAnalyticsService, $salesAnalyticsService);
                    $warmedCaches[] = $type;
                } catch (\Exception $e) {
                    Log::warning("Failed to warm {$type} cache", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Report cache warming completed', [
                'warmed_caches' => $warmedCaches,
                'execution_time_ms' => $executionTime,
                'total_types' => count($warmedCaches)
            ]);

        } catch (\Exception $e) {
            Log::error('Report cache warming failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Warm specific cache type
     */
    protected function warmCacheType(
        string $type,
        ReportCacheService $cacheService,
        BusinessReportService $businessReportService,
        ReportDataService $reportDataService,
        ManifestAnalyticsService $manifestAnalyticsService,
        SalesAnalyticsService $salesAnalyticsService
    ): void {
        switch ($type) {
            case 'sales':
                $this->warmSalesCache($cacheService, $businessReportService, $reportDataService);
                break;
                
            case 'manifest':
                $this->warmManifestCache($cacheService, $manifestAnalyticsService, $reportDataService);
                break;
                
            case 'customer':
                $this->warmCustomerCache($cacheService, $reportDataService);
                break;
                
            case 'financial':
                $this->warmFinancialCache($cacheService, $businessReportService, $salesAnalyticsService);
                break;
                
            case 'dashboard':
                $this->warmDashboardCache($cacheService, $businessReportService, $reportDataService);
                break;
                
            default:
                Log::warning("Unknown cache type: {$type}");
        }
    }

    /**
     * Warm sales report cache
     */
    protected function warmSalesCache(
        ReportCacheService $cacheService,
        BusinessReportService $businessReportService,
        ReportDataService $reportDataService
    ): void {
        $commonFilters = $this->getCommonDateFilters();
        
        foreach ($commonFilters as $filterName => $filters) {
            $mergedFilters = array_merge($this->filters, $filters);
            
            // Warm sales collections data
            $salesData = $reportDataService->getSalesCollectionsData($mergedFilters);
            $cacheService->cacheSalesData($mergedFilters, $salesData);
            
            // Warm business report data
            $businessData = $businessReportService->generateSalesCollectionsReport($mergedFilters);
            $cacheKey = "sales:business_report:" . md5(serialize($mergedFilters));
            $cacheService->cacheReportData($cacheKey, $businessData, 60);
            
            Log::debug("Warmed sales cache for filter: {$filterName}");
        }
    }

    /**
     * Warm manifest analytics cache
     */
    protected function warmManifestCache(
        ReportCacheService $cacheService,
        ManifestAnalyticsService $manifestAnalyticsService,
        ReportDataService $reportDataService
    ): void {
        $commonFilters = $this->getCommonDateFilters();
        $manifestTypes = ['air', 'sea'];
        
        foreach ($commonFilters as $filterName => $filters) {
            $mergedFilters = array_merge($this->filters, $filters);
            
            // Warm general manifest metrics
            $manifestData = $reportDataService->getManifestMetrics($mergedFilters);
            $cacheService->cacheManifestData($mergedFilters, $manifestData);
            
            // Warm by manifest type
            foreach ($manifestTypes as $type) {
                $typeFilters = array_merge($mergedFilters, ['manifest_type' => $type]);
                
                $typeData = $reportDataService->getManifestMetrics($typeFilters);
                $cacheService->cacheManifestData($typeFilters, $typeData);
                
                // Warm analytics data
                $analyticsData = $manifestAnalyticsService->getEfficiencyMetrics($typeFilters);
                $cacheKey = "manifest:analytics:{$type}:" . md5(serialize($typeFilters));
                $cacheService->cacheReportData($cacheKey, $analyticsData, 60);
            }
            
            Log::debug("Warmed manifest cache for filter: {$filterName}");
        }
    }

    /**
     * Warm customer analytics cache
     */
    protected function warmCustomerCache(
        ReportCacheService $cacheService,
        ReportDataService $reportDataService
    ): void {
        $commonFilters = $this->getCommonDateFilters();
        
        foreach ($commonFilters as $filterName => $filters) {
            $mergedFilters = array_merge($this->filters, $filters);
            
            // Warm customer statistics
            $customerData = $reportDataService->getCustomerStatistics($mergedFilters);
            $cacheService->cacheCustomerData($mergedFilters, $customerData);
            
            Log::debug("Warmed customer cache for filter: {$filterName}");
        }
    }

    /**
     * Warm financial analytics cache
     */
    protected function warmFinancialCache(
        ReportCacheService $cacheService,
        BusinessReportService $businessReportService,
        SalesAnalyticsService $salesAnalyticsService
    ): void {
        $commonFilters = $this->getCommonDateFilters();
        
        foreach ($commonFilters as $filterName => $filters) {
            $mergedFilters = array_merge($this->filters, $filters);
            
            // Warm financial summary
            $financialData = $businessReportService->generateFinancialSummaryReport($mergedFilters);
            $cacheKey = "financial:summary:" . md5(serialize($mergedFilters));
            $cacheService->cacheReportData($cacheKey, $financialData, 60);
            
            // Warm collection rates
            $collectionData = $salesAnalyticsService->calculateCollectionRates($mergedFilters);
            $cacheKey = "financial:collections:" . md5(serialize($mergedFilters));
            $cacheService->cacheReportData($cacheKey, $collectionData, 60);
            
            Log::debug("Warmed financial cache for filter: {$filterName}");
        }
    }

    /**
     * Warm dashboard widget cache
     */
    protected function warmDashboardCache(
        ReportCacheService $cacheService,
        BusinessReportService $businessReportService,
        ReportDataService $reportDataService
    ): void {
        $dashboardFilters = [
            'today' => [
                'date_from' => Carbon::today()->toDateString(),
                'date_to' => Carbon::today()->toDateString()
            ],
            'this_week' => [
                'date_from' => Carbon::now()->startOfWeek()->toDateString(),
                'date_to' => Carbon::now()->endOfWeek()->toDateString()
            ],
            'this_month' => [
                'date_from' => Carbon::now()->startOfMonth()->toDateString(),
                'date_to' => Carbon::now()->endOfMonth()->toDateString()
            ]
        ];

        foreach ($dashboardFilters as $period => $filters) {
            $mergedFilters = array_merge($this->filters, $filters);
            
            // Warm key dashboard metrics
            $salesSummary = $businessReportService->generateSalesCollectionsReport($mergedFilters);
            $cacheService->cacheDashboardWidget("sales_summary_{$period}", $salesSummary);
            
            $manifestMetrics = $reportDataService->getManifestMetrics($mergedFilters);
            $cacheService->cacheDashboardWidget("manifest_metrics_{$period}", $manifestMetrics);
            
            $financialBreakdown = $reportDataService->getFinancialBreakdown($mergedFilters);
            $cacheService->cacheDashboardWidget("financial_breakdown_{$period}", $financialBreakdown);
            
            Log::debug("Warmed dashboard cache for period: {$period}");
        }
    }

    /**
     * Get common date filter combinations for cache warming
     */
    protected function getCommonDateFilters(): array
    {
        return [
            'last_7_days' => [
                'date_from' => Carbon::now()->subDays(7)->toDateString(),
                'date_to' => Carbon::now()->toDateString()
            ],
            'last_30_days' => [
                'date_from' => Carbon::now()->subDays(30)->toDateString(),
                'date_to' => Carbon::now()->toDateString()
            ],
            'last_90_days' => [
                'date_from' => Carbon::now()->subDays(90)->toDateString(),
                'date_to' => Carbon::now()->toDateString()
            ],
            'this_month' => [
                'date_from' => Carbon::now()->startOfMonth()->toDateString(),
                'date_to' => Carbon::now()->endOfMonth()->toDateString()
            ],
            'last_month' => [
                'date_from' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'date_to' => Carbon::now()->subMonth()->endOfMonth()->toDateString()
            ],
            'this_year' => [
                'date_from' => Carbon::now()->startOfYear()->toDateString(),
                'date_to' => Carbon::now()->endOfYear()->toDateString()
            ]
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Report cache warming job failed', [
            'cache_types' => $this->cacheTypes,
            'filters' => $this->filters,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
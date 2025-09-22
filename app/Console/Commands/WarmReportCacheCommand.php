<?php

namespace App\Console\Commands;

use App\Services\ReportCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WarmReportCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:warm-cache 
                            {--days=30 : Number of days to include in cache warmup}
                            {--types=* : Specific report types to warm up (sales,manifest,customer,dashboard)}
                            {--force : Force cache warmup even if cache exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up the report cache with frequently accessed data';

    /**
     * The report cache service
     *
     * @var ReportCacheService
     */
    protected ReportCacheService $reportCacheService;

    /**
     * Create a new command instance.
     *
     * @param ReportCacheService $reportCacheService
     */
    public function __construct(ReportCacheService $reportCacheService)
    {
        parent::__construct();
        $this->reportCacheService = $reportCacheService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting report cache warmup...');
        
        $days = (int) $this->option('days');
        $types = $this->option('types');
        $force = $this->option('force');
        
        // Default to all types if none specified
        if (empty($types)) {
            $types = ['sales', 'manifest', 'customer', 'dashboard'];
        }
        
        // Prepare base filters
        $baseFilters = [
            'date_from' => Carbon::now()->subDays($days)->toDateString()
        ];
        
        $this->info("Warming cache for last {$days} days...");
        $this->info('Report types: ' . implode(', ', $types));
        
        try {
            // Check cache health first
            $this->checkCacheHealth();
            
            // Warm up each requested type
            foreach ($types as $type) {
                $this->warmupReportType($type, $baseFilters, $force);
            }
            
            // Display cache statistics
            $this->displayCacheStats();
            
            $this->info('Report cache warmup completed successfully!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Cache warmup failed: ' . $e->getMessage());
            Log::error('Report cache warmup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Check cache health before warming up
     */
    private function checkCacheHealth(): void
    {
        $this->info('Checking cache health...');
        
        $stats = $this->reportCacheService->getCacheStats();
        
        if (!$stats['cache_health']) {
            $this->warn('Cache health check failed. Proceeding anyway...');
        } else {
            $this->info('✓ Cache is healthy');
        }
        
        $this->info('Cache driver: ' . $stats['cache_driver']);
    }

    /**
     * Warm up cache for a specific report type
     */
    private function warmupReportType(string $type, array $baseFilters, bool $force): void
    {
        $this->info("Warming up {$type} reports...");
        
        $progressBar = $this->output->createProgressBar();
        $progressBar->start();
        
        try {
            switch ($type) {
                case 'sales':
                    $this->warmupSalesReports($baseFilters, $force, $progressBar);
                    break;
                    
                case 'manifest':
                    $this->warmupManifestReports($baseFilters, $force, $progressBar);
                    break;
                    
                case 'customer':
                    $this->warmupCustomerReports($baseFilters, $force, $progressBar);
                    break;
                    
                case 'dashboard':
                    $this->warmupDashboardReports($baseFilters, $force, $progressBar);
                    break;
                    
                default:
                    $this->warn("Unknown report type: {$type}");
                    return;
            }
            
            $progressBar->finish();
            $this->newLine();
            $this->info("✓ {$type} reports warmed up");
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error("✗ Failed to warm up {$type} reports: " . $e->getMessage());
        }
    }

    /**
     * Warm up sales reports
     */
    private function warmupSalesReports(array $baseFilters, bool $force, $progressBar): void
    {
        $filterCombinations = [
            $baseFilters, // Base filters
            array_merge($baseFilters, ['date_from' => Carbon::now()->subDays(7)->toDateString()]), // Last 7 days
            array_merge($baseFilters, ['date_from' => Carbon::now()->subDays(1)->toDateString()]), // Last 24 hours
        ];
        
        foreach ($filterCombinations as $filters) {
            $progressBar->advance();
            
            // Check if cache exists and skip if not forcing
            if (!$force && $this->reportCacheService->getCachedSalesData($filters)) {
                continue;
            }
            
            // Use the BusinessReportService to generate and cache data
            $businessReportService = app(\App\Services\BusinessReportService::class);
            $data = $businessReportService->generateSalesCollectionsReport($filters);
            $this->reportCacheService->cacheSalesData($filters, $data);
        }
    }

    /**
     * Warm up manifest reports
     */
    private function warmupManifestReports(array $baseFilters, bool $force, $progressBar): void
    {
        $filterCombinations = [
            $baseFilters,
            array_merge($baseFilters, ['type' => 'air']),
            array_merge($baseFilters, ['type' => 'sea']),
            array_merge($baseFilters, ['status' => 'open']),
            array_merge($baseFilters, ['status' => 'closed']),
        ];
        
        foreach ($filterCombinations as $filters) {
            $progressBar->advance();
            
            if (!$force && $this->reportCacheService->getCachedManifestData($filters)) {
                continue;
            }
            
            $manifestAnalyticsService = app(\App\Services\ManifestAnalyticsService::class);
            $data = $manifestAnalyticsService->getEfficiencyMetrics($filters);
            $this->reportCacheService->cacheManifestData($filters, $data);
        }
    }

    /**
     * Warm up customer reports
     */
    private function warmupCustomerReports(array $baseFilters, bool $force, $progressBar): void
    {
        $filterCombinations = [
            $baseFilters,
            array_merge($baseFilters, ['date_from' => Carbon::now()->subDays(7)->toDateString()]),
        ];
        
        foreach ($filterCombinations as $filters) {
            $progressBar->advance();
            
            if (!$force && $this->reportCacheService->getCachedCustomerData($filters)) {
                continue;
            }
            
            // Placeholder for customer analytics - will be implemented when service is available
            $data = ['placeholder' => true, 'filters' => $filters];
            $this->reportCacheService->cacheCustomerData($filters, $data);
        }
    }

    /**
     * Warm up dashboard reports
     */
    private function warmupDashboardReports(array $baseFilters, bool $force, $progressBar): void
    {
        $widgets = [
            'sales_summary',
            'manifest_count',
            'recent_activity',
            'financial_overview',
            'processing_metrics'
        ];
        
        foreach ($widgets as $widget) {
            $progressBar->advance();
            
            if (!$force && $this->reportCacheService->getCachedDashboardWidget($widget)) {
                continue;
            }
            
            // Generate widget data based on type
            $data = $this->generateWidgetData($widget, $baseFilters);
            $this->reportCacheService->cacheDashboardWidget($widget, $data);
        }
    }

    /**
     * Generate widget data for dashboard
     */
    private function generateWidgetData(string $widget, array $filters): array
    {
        switch ($widget) {
            case 'sales_summary':
                $businessReportService = app(\App\Services\BusinessReportService::class);
                return $businessReportService->generateFinancialSummaryReport($filters);
                
            case 'manifest_count':
                $manifestAnalyticsService = app(\App\Services\ManifestAnalyticsService::class);
                return $manifestAnalyticsService->getEfficiencyMetrics($filters);
                
            default:
                return [
                    'widget_type' => $widget,
                    'generated_at' => now()->toISOString(),
                    'filters' => $filters,
                    'placeholder' => true
                ];
        }
    }

    /**
     * Display cache statistics
     */
    private function displayCacheStats(): void
    {
        $this->info('Cache Statistics:');
        
        $stats = $this->reportCacheService->getCacheStats();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Driver', $stats['cache_driver']],
                ['Cache Health', $stats['cache_health'] ? '✓ Healthy' : '✗ Unhealthy'],
                ['Memory Usage', $stats['memory_usage']['usage'] ?? 'N/A'],
            ]
        );
        
        if (isset($stats['key_counts']) && !empty($stats['key_counts'])) {
            $this->info('Cache Key Counts by Type:');
            foreach ($stats['key_counts'] as $type => $count) {
                $this->line("  {$type}: {$count}");
            }
        }
    }
}
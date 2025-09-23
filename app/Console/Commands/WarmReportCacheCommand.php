<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportCacheService;
use App\Jobs\WarmReportCacheJob;
use Carbon\Carbon;

class WarmReportCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:warm-cache 
                            {--types=* : Cache types to warm (sales, manifest, customer, financial, dashboard, all)}
                            {--async : Run cache warming in background jobs}
                            {--date-from= : Start date for cache warming (YYYY-MM-DD)}
                            {--date-to= : End date for cache warming (YYYY-MM-DD)}
                            {--force : Force cache warming even if cache exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up report cache for frequently accessed data';

    protected ReportCacheService $cacheService;

    public function __construct(ReportCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Report Cache Warming Tool');
        $this->line('===========================');

        // Get cache types to warm
        $types = $this->option('types');
        if (empty($types)) {
            $types = ['all'];
        }

        // Prepare filters
        $filters = [];
        if ($this->option('date-from')) {
            $filters['date_from'] = Carbon::parse($this->option('date-from'))->toDateString();
        }
        if ($this->option('date-to')) {
            $filters['date_to'] = Carbon::parse($this->option('date-to'))->toDateString();
        }

        $this->info('Cache warming configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Cache Types', implode(', ', $types)],
                ['Async Mode', $this->option('async') ? 'Yes' : 'No'],
                ['Date From', $filters['date_from'] ?? 'Default (30 days ago)'],
                ['Date To', $filters['date_to'] ?? 'Default (today)'],
                ['Force Refresh', $this->option('force') ? 'Yes' : 'No']
            ]
        );

        if (!$this->confirm('Proceed with cache warming?')) {
            $this->info('Cache warming cancelled.');
            return 0;
        }

        if ($this->option('async')) {
            return $this->warmCacheAsync($types, $filters);
        } else {
            return $this->warmCacheSync($types, $filters);
        }
    }

    /**
     * Warm cache asynchronously using jobs
     */
    protected function warmCacheAsync(array $types, array $filters): int
    {
        try {
            $this->info('Dispatching cache warming jobs...');
            
            // Dispatch job for each cache type to distribute load
            foreach ($types as $type) {
                if ($type === 'all') {
                    WarmReportCacheJob::dispatch(['all'], $filters)->onQueue('reports');
                    $this->info('Dispatched job for all cache types');
                    break;
                } else {
                    WarmReportCacheJob::dispatch([$type], $filters)->onQueue('reports');
                    $this->info("Dispatched job for {$type} cache");
                }
            }

            $this->info('Cache warming jobs dispatched successfully!');
            $this->line('Monitor job progress with: php artisan queue:work --queue=reports');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to dispatch cache warming jobs: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Warm cache synchronously
     */
    protected function warmCacheSync(array $types, array $filters): int
    {
        try {
            $this->info('Starting synchronous cache warming...');
            $startTime = microtime(true);

            // Clear existing cache if force option is used
            if ($this->option('force')) {
                $this->info('Clearing existing cache...');
                $this->cacheService->clearAllReportCache();
            }

            // Determine types to warm
            $typesToWarm = in_array('all', $types) ? 
                ['sales', 'manifest', 'customer', 'financial', 'dashboard'] : 
                $types;

            $progressBar = $this->output->createProgressBar(count($typesToWarm));
            $progressBar->start();

            foreach ($typesToWarm as $type) {
                $this->warmCacheType($type, $filters);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line('');

            $executionTime = round((microtime(true) - $startTime), 2);
            $this->info("Cache warming completed in {$executionTime} seconds");

            // Show cache statistics
            $this->showCacheStatistics();

            return 0;
        } catch (\Exception $e) {
            $this->error('Cache warming failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Warm specific cache type
     */
    protected function warmCacheType(string $type, array $filters): void
    {
        switch ($type) {
            case 'sales':
                $this->warmSalesCache($filters);
                break;
            case 'manifest':
                $this->warmManifestCache($filters);
                break;
            case 'customer':
                $this->warmCustomerCache($filters);
                break;
            case 'financial':
                $this->warmFinancialCache($filters);
                break;
            case 'dashboard':
                $this->warmDashboardCache($filters);
                break;
            default:
                $this->warn("Unknown cache type: {$type}");
        }
    }

    /**
     * Warm sales cache
     */
    protected function warmSalesCache(array $filters): void
    {
        $commonFilters = $this->getCommonDateFilters($filters);
        
        foreach ($commonFilters as $filterSet) {
            try {
                $this->cacheService->warmUpReportCache($filterSet);
            } catch (\Exception $e) {
                $this->warn("Failed to warm sales cache: {$e->getMessage()}");
            }
        }
    }

    /**
     * Warm manifest cache
     */
    protected function warmManifestCache(array $filters): void
    {
        $commonFilters = $this->getCommonDateFilters($filters);
        $manifestTypes = ['air', 'sea'];
        
        foreach ($commonFilters as $filterSet) {
            foreach ($manifestTypes as $type) {
                try {
                    $typeFilters = array_merge($filterSet, ['manifest_type' => $type]);
                    $this->cacheService->warmUpReportCache($typeFilters);
                } catch (\Exception $e) {
                    $this->warn("Failed to warm manifest cache for {$type}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Warm customer cache
     */
    protected function warmCustomerCache(array $filters): void
    {
        $commonFilters = $this->getCommonDateFilters($filters);
        
        foreach ($commonFilters as $filterSet) {
            try {
                $this->cacheService->warmUpReportCache($filterSet);
            } catch (\Exception $e) {
                $this->warn("Failed to warm customer cache: {$e->getMessage()}");
            }
        }
    }

    /**
     * Warm financial cache
     */
    protected function warmFinancialCache(array $filters): void
    {
        $commonFilters = $this->getCommonDateFilters($filters);
        
        foreach ($commonFilters as $filterSet) {
            try {
                $this->cacheService->warmUpReportCache($filterSet);
            } catch (\Exception $e) {
                $this->warn("Failed to warm financial cache: {$e->getMessage()}");
            }
        }
    }

    /**
     * Warm dashboard cache
     */
    protected function warmDashboardCache(array $filters): void
    {
        $dashboardPeriods = [
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

        foreach ($dashboardPeriods as $period => $periodFilters) {
            try {
                $mergedFilters = array_merge($filters, $periodFilters);
                $this->cacheService->warmUpReportCache($mergedFilters);
            } catch (\Exception $e) {
                $this->warn("Failed to warm dashboard cache for {$period}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Get common date filter combinations
     */
    protected function getCommonDateFilters(array $baseFilters): array
    {
        $filters = [];
        
        // Use provided date range or defaults
        $dateFrom = $baseFilters['date_from'] ?? Carbon::now()->subDays(30)->toDateString();
        $dateTo = $baseFilters['date_to'] ?? Carbon::now()->toDateString();
        
        $filters[] = array_merge($baseFilters, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        // Add common period filters if not using custom dates
        if (!isset($baseFilters['date_from']) && !isset($baseFilters['date_to'])) {
            $commonPeriods = [
                [
                    'date_from' => Carbon::now()->subDays(7)->toDateString(),
                    'date_to' => Carbon::now()->toDateString()
                ],
                [
                    'date_from' => Carbon::now()->startOfMonth()->toDateString(),
                    'date_to' => Carbon::now()->endOfMonth()->toDateString()
                ],
                [
                    'date_from' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                    'date_to' => Carbon::now()->subMonth()->endOfMonth()->toDateString()
                ]
            ];

            foreach ($commonPeriods as $period) {
                $filters[] = array_merge($baseFilters, $period);
            }
        }

        return $filters;
    }

    /**
     * Show cache statistics after warming
     */
    protected function showCacheStatistics(): void
    {
        try {
            $stats = $this->cacheService->getCacheStats();
            
            $this->line('');
            $this->info('Cache Statistics:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Cache Driver', $stats['cache_driver']],
                    ['Cache Health', $stats['cache_health'] ? 'Healthy' : 'Unhealthy'],
                    ['Memory Usage', $stats['memory_usage']['usage'] ?? 'N/A']
                ]
            );
        } catch (\Exception $e) {
            $this->warn('Could not retrieve cache statistics: ' . $e->getMessage());
        }
    }
}
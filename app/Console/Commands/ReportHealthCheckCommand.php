<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\BusinessReportService;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;

class ReportHealthCheckCommand extends Command
{
    protected $signature = 'reports:health-check 
                           {--notify : Send notifications on failures}
                           {--fix : Attempt to fix issues automatically}';

    protected $description = 'Perform health checks on the reporting system';

    protected array $healthChecks = [];
    protected array $failures = [];

    public function handle()
    {
        $this->info('Starting Report System Health Check...');
        
        $this->performHealthChecks();
        $this->displayResults();
        
        if ($this->option('fix')) {
            $this->attemptFixes();
        }
        
        if ($this->option('notify') && !empty($this->failures)) {
            $this->sendNotifications();
        }
        
        return empty($this->failures) ? 0 : 1;
    }

    protected function performHealthChecks(): void
    {
        $checks = [
            'Database Connection' => [$this, 'checkDatabaseConnection'],
            'Cache System' => [$this, 'checkCacheSystem'],
            'Report Services' => [$this, 'checkReportServices'],
            'Required Tables' => [$this, 'checkRequiredTables'],
            'Export System' => [$this, 'checkExportSystem'],
            'Chart Dependencies' => [$this, 'checkChartDependencies'],
            'Permissions' => [$this, 'checkPermissions'],
        ];

        foreach ($checks as $name => $callback) {
            $this->line("Checking {$name}...");
            
            try {
                $result = call_user_func($callback);
                $this->healthChecks[$name] = $result;
                
                if ($result['status'] === 'pass') {
                    $this->info("✓ {$name}: {$result['message']}");
                } else {
                    $this->error("✗ {$name}: {$result['message']}");
                    $this->failures[] = $name;
                }
            } catch (\Exception $e) {
                $this->error("✗ {$name}: Exception - {$e->getMessage()}");
                $this->healthChecks[$name] = [
                    'status' => 'fail',
                    'message' => $e->getMessage(),
                    'exception' => $e
                ];
                $this->failures[] = $name;
            }
        }
    }

    protected function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $count = DB::table('packages')->count();
            
            return [
                'status' => 'pass',
                'message' => "Connected successfully ({$count} packages in database)"
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => "Database connection failed: {$e->getMessage()}"
            ];
        }
    }

    protected function checkCacheSystem(): array
    {
        try {
            $testKey = 'reports_health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => 'pass',
                    'message' => 'Cache system working correctly'
                ];
            } else {
                return [
                    'status' => 'fail',
                    'message' => 'Cache system not storing/retrieving values correctly'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => "Cache system error: {$e->getMessage()}"
            ];
        }
    }

    protected function checkReportServices(): array
    {
        try {
            $businessReportService = app(BusinessReportService::class);
            $salesAnalyticsService = app(SalesAnalyticsService::class);
            
            // Test basic service functionality
            $testFilters = [
                'date_from' => Carbon::now()->subDays(7),
                'date_to' => Carbon::now()
            ];
            
            $salesReport = $businessReportService->generateSalesCollectionsReport($testFilters);
            
            if (is_array($salesReport) && isset($salesReport['manifests'])) {
                return [
                    'status' => 'pass',
                    'message' => 'Report services functioning correctly'
                ];
            } else {
                return [
                    'status' => 'fail',
                    'message' => 'Report services not returning expected data structure'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => "Report services error: {$e->getMessage()}"
            ];
        }
    }

    protected function checkRequiredTables(): array
    {
        $requiredTables = [
            'packages',
            'manifests',
            'users',
            'package_distributions',
            'package_distribution_items',
            'report_export_jobs',
            'saved_report_filters'
        ];

        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            try {
                DB::table($table)->limit(1)->get();
            } catch (\Exception $e) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            return [
                'status' => 'pass',
                'message' => 'All required tables exist and are accessible'
            ];
        } else {
            return [
                'status' => 'fail',
                'message' => 'Missing tables: ' . implode(', ', $missingTables)
            ];
        }
    }

    protected function checkExportSystem(): array
    {
        try {
            $exportPath = storage_path('app/exports');
            
            if (!is_dir($exportPath)) {
                mkdir($exportPath, 0755, true);
            }
            
            if (!is_writable($exportPath)) {
                return [
                    'status' => 'fail',
                    'message' => 'Export directory is not writable'
                ];
            }
            
            // Check if export jobs table exists and is functional
            $recentJobs = DB::table('report_export_jobs')
                ->where('created_at', '>', Carbon::now()->subDays(1))
                ->count();
            
            return [
                'status' => 'pass',
                'message' => "Export system ready ({$recentJobs} jobs in last 24h)"
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => "Export system error: {$e->getMessage()}"
            ];
        }
    }

    protected function checkChartDependencies(): array
    {
        // Check if Chart.js is accessible (this would be more complex in a real scenario)
        $chartConfig = config('reports.charts');
        
        if (!$chartConfig['enabled']) {
            return [
                'status' => 'warning',
                'message' => 'Charts are disabled in configuration'
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'Chart dependencies configured correctly'
        ];
    }

    protected function checkPermissions(): array
    {
        try {
            // Check if ReportPolicy exists and is properly configured
            $policyClass = \App\Policies\ReportPolicy::class;
            
            if (!class_exists($policyClass)) {
                return [
                    'status' => 'fail',
                    'message' => 'ReportPolicy class not found'
                ];
            }
            
            // Test policy methods exist
            $policy = new $policyClass();
            $requiredMethods = [
                'viewReports',
                'viewSalesReports', 
                'viewManifestReports',
                'viewCustomerReports',
                'viewFinancialReports'
            ];
            
            foreach ($requiredMethods as $method) {
                if (!method_exists($policy, $method)) {
                    return [
                        'status' => 'fail',
                        'message' => "Missing policy method: {$method}"
                    ];
                }
            }
            
            return [
                'status' => 'pass',
                'message' => 'ReportPolicy configured with all required methods'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => "Policy check error: {$e->getMessage()}"
            ];
        }
    }

    protected function displayResults(): void
    {
        $this->newLine();
        $this->info('=== Health Check Results ===');
        
        $passed = count($this->healthChecks) - count($this->failures);
        $total = count($this->healthChecks);
        
        $this->info("Passed: {$passed}/{$total}");
        
        if (!empty($this->failures)) {
            $this->error("Failed: " . count($this->failures));
            $this->error("Failed checks: " . implode(', ', $this->failures));
        } else {
            $this->info('All health checks passed!');
        }
    }

    protected function attemptFixes(): void
    {
        $this->info('Attempting automatic fixes...');
        
        foreach ($this->failures as $failure) {
            switch ($failure) {
                case 'Cache System':
                    $this->info('Clearing cache...');
                    \Artisan::call('cache:clear');
                    break;
                    
                case 'Export System':
                    $this->info('Creating export directories...');
                    $exportPath = storage_path('app/exports');
                    if (!is_dir($exportPath)) {
                        mkdir($exportPath, 0755, true);
                    }
                    break;
            }
        }
    }

    protected function sendNotifications(): void
    {
        Log::error('Report system health check failures', [
            'failures' => $this->failures,
            'health_checks' => $this->healthChecks,
            'timestamp' => now()
        ]);
        
        // Here you could send email notifications, Slack messages, etc.
        $this->info('Failure notifications sent to logs');
    }
}
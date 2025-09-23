<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportQueryOptimizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeReportQueriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:optimize-queries 
                            {--analyze : Analyze current query performance}
                            {--indexes : Check and suggest database indexes}
                            {--clear-log : Clear query performance log}
                            {--threshold=1000 : Set slow query threshold in milliseconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize and monitor report database queries';

    protected ReportQueryOptimizationService $optimizationService;

    public function __construct(ReportQueryOptimizationService $optimizationService)
    {
        parent::__construct();
        $this->optimizationService = $optimizationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Report Query Optimization Tool');
        $this->line('================================');

        // Set threshold if provided
        if ($this->option('threshold')) {
            $threshold = (int) $this->option('threshold');
            $this->optimizationService->setSlowQueryThreshold($threshold);
            $this->info("Set slow query threshold to {$threshold}ms");
        }

        // Clear performance log if requested
        if ($this->option('clear-log')) {
            $this->optimizationService->clearPerformanceLog();
            $this->info('Query performance log cleared');
            return 0;
        }

        // Analyze query performance
        if ($this->option('analyze')) {
            $this->analyzeQueryPerformance();
        }

        // Check database indexes
        if ($this->option('indexes')) {
            $this->checkDatabaseIndexes();
        }

        // If no specific options, run all checks
        if (!$this->option('analyze') && !$this->option('indexes')) {
            $this->analyzeQueryPerformance();
            $this->line('');
            $this->checkDatabaseIndexes();
        }

        return 0;
    }

    /**
     * Analyze query performance and show recommendations
     */
    protected function analyzeQueryPerformance(): void
    {
        $this->info('Analyzing Query Performance...');
        $this->line('');

        $stats = $this->optimizationService->getPerformanceStatistics();

        // Display performance metrics
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Queries', $stats['total_queries']],
                ['Slow Queries', $stats['slow_queries']],
                ['Slow Query %', $stats['slow_query_percentage'] . '%'],
                ['Cache Hit Rate', $stats['cache_hit_rate'] . '%'],
                ['Avg Execution Time', $stats['average_execution_time'] . 'ms']
            ]
        );

        // Display recommendations
        if (!empty($stats['recommendations'])) {
            $this->line('');
            $this->warn('Performance Recommendations:');
            foreach ($stats['recommendations'] as $recommendation) {
                $priority = strtoupper($recommendation['priority']);
                $this->line("  [{$priority}] {$recommendation['message']}");
            }
        } else {
            $this->line('');
            $this->info('✓ No performance issues detected');
        }
    }

    /**
     * Check database indexes and suggest optimizations
     */
    protected function checkDatabaseIndexes(): void
    {
        $this->info('Checking Database Indexes...');
        $this->line('');

        $indexChecks = [
            'packages' => [
                'created_at_status_index' => ['created_at', 'status'],
                'manifest_id_status_index' => ['manifest_id', 'status'],
                'office_id_created_at_index' => ['office_id', 'created_at'],
                'user_id_created_at_status_index' => ['user_id', 'created_at', 'status'],
                'created_at_status_freight_price_index' => ['created_at', 'status', 'freight_price']
            ],
            'customer_transactions' => [
                'type_created_at_index' => ['type', 'created_at'],
                'user_id_type_created_at_index' => ['user_id', 'type', 'created_at'],
                'type_amount_created_at_index' => ['type', 'amount', 'created_at']
            ],
            'manifests' => [
                'type_created_at_index' => ['type', 'created_at'],
                'shipment_date_type_index' => ['shipment_date', 'type']
            ],
            'users' => [
                'created_at_index' => ['created_at'],
                'account_balance_created_at_index' => ['account_balance', 'created_at'],
                'first_name_last_name_index' => ['first_name', 'last_name']
            ]
        ];

        $missingIndexes = [];
        $existingIndexes = [];

        foreach ($indexChecks as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                $this->warn("Table '{$table}' does not exist");
                continue;
            }

            foreach ($indexes as $indexName => $columns) {
                if ($this->indexExists($table, $indexName)) {
                    $existingIndexes[] = "{$table}.{$indexName}";
                } else {
                    $missingIndexes[] = [
                        'table' => $table,
                        'index' => $indexName,
                        'columns' => implode(', ', $columns)
                    ];
                }
            }
        }

        // Display existing indexes
        if (!empty($existingIndexes)) {
            $this->info('✓ Existing Report Indexes:');
            foreach ($existingIndexes as $index) {
                $this->line("  {$index}");
            }
        }

        // Display missing indexes
        if (!empty($missingIndexes)) {
            $this->line('');
            $this->warn('Missing Recommended Indexes:');
            $this->table(
                ['Table', 'Index Name', 'Columns'],
                array_map(function($index) {
                    return [$index['table'], $index['index'], $index['columns']];
                }, $missingIndexes)
            );

            $this->line('');
            $this->info('To add missing indexes, run: php artisan migrate');
        } else {
            $this->line('');
            $this->info('✓ All recommended indexes are present');
        }

        // Check for unused indexes (basic check)
        $this->checkUnusedIndexes();
    }

    /**
     * Check for potentially unused indexes
     */
    protected function checkUnusedIndexes(): void
    {
        $this->line('');
        $this->info('Checking for potentially unused indexes...');

        // This is a simplified check - in production, you'd want to use
        // performance_schema or similar tools for accurate usage statistics
        $tables = ['packages', 'customer_transactions', 'manifests', 'users'];
        $potentiallyUnused = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $indexes = DB::select("SHOW INDEX FROM {$table}");
                foreach ($indexes as $index) {
                    // Skip primary keys and unique indexes
                    if ($index->Key_name === 'PRIMARY' || $index->Non_unique == 0) {
                        continue;
                    }

                    // Simple heuristic: if index name doesn't contain common report terms
                    $reportTerms = ['created_at', 'status', 'type', 'amount', 'user_id', 'manifest_id'];
                    $hasReportTerm = false;
                    foreach ($reportTerms as $term) {
                        if (strpos($index->Column_name, $term) !== false) {
                            $hasReportTerm = true;
                            break;
                        }
                    }

                    if (!$hasReportTerm) {
                        $potentiallyUnused[] = "{$table}.{$index->Key_name} ({$index->Column_name})";
                    }
                }
            } catch (\Exception $e) {
                // Skip if we can't check indexes for this table
                continue;
            }
        }

        if (!empty($potentiallyUnused)) {
            $this->warn('Potentially unused indexes (manual review recommended):');
            foreach ($potentiallyUnused as $index) {
                $this->line("  {$index}");
            }
        } else {
            $this->info('✓ No obviously unused indexes detected');
        }
    }

    /**
     * Check if an index exists on a table
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
}
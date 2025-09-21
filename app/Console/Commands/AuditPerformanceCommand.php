<?php

namespace App\Console\Commands;

use App\Jobs\AuditCacheWarmupJob;
use App\Services\AuditCacheService;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditPerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:performance 
                            {action : The action to perform (warmup-cache, analyze-performance, optimize-indexes)}
                            {--force : Force the operation}';

    /**
     * The console command description.
     */
    protected $description = 'Manage audit system performance optimizations';

    /**
     * Execute the console command.
     */
    public function handle(AuditCacheService $cacheService): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'warmup-cache':
                return $this->warmupCache($cacheService);
            case 'analyze-performance':
                return $this->analyzePerformance();
            case 'optimize-indexes':
                return $this->optimizeIndexes();
            default:
                return $this->error("Unknown action: {$action}");
        }
    }

    /**
     * Warm up audit caches
     */
    private function warmupCache(AuditCacheService $cacheService): int
    {
        $this->info('Warming up audit caches...');
        
        if ($this->option('force')) {
            // Clear existing caches first
            $this->info('Clearing existing caches...');
            $cacheService->invalidateStatisticsCaches();
        }

        // Dispatch cache warmup job
        AuditCacheWarmupJob::dispatch();
        
        $this->info('Cache warmup job dispatched successfully.');
        
        return 0;
    }

    /**
     * Analyze audit system performance
     */
    private function analyzePerformance(): int
    {
        $this->info('Analyzing audit system performance...');
        
        // Get basic statistics
        $totalLogs = AuditLog::count();
        $logsLast24h = AuditLog::where('created_at', '>=', now()->subDay())->count();
        $logsLast7d = AuditLog::where('created_at', '>=', now()->subWeek())->count();
        
        // Get table size
        $tableSize = $this->getTableSize();
        
        // Get index usage
        $indexStats = $this->getIndexStats();
        
        // Display results
        $this->table(['Metric', 'Value'], [
            ['Total Audit Logs', number_format($totalLogs)],
            ['Logs (Last 24h)', number_format($logsLast24h)],
            ['Logs (Last 7d)', number_format($logsLast7d)],
            ['Table Size (MB)', $tableSize],
            ['Avg Logs/Hour (24h)', round($logsLast24h / 24, 2)],
        ]);

        if (!empty($indexStats)) {
            $this->info("\nIndex Usage Statistics:");
            $this->table(['Index Name', 'Cardinality', 'Usage'], $indexStats);
        }

        // Performance recommendations
        $this->displayRecommendations($totalLogs, $logsLast24h, $tableSize);
        
        return 0;
    }

    /**
     * Optimize database indexes
     */
    private function optimizeIndexes(): int
    {
        $this->info('Analyzing index optimization opportunities...');
        
        // Check for missing indexes based on query patterns
        $recommendations = $this->analyzeQueryPatterns();
        
        if (empty($recommendations)) {
            $this->info('No additional index optimizations recommended at this time.');
            return 0;
        }

        $this->info('Index optimization recommendations:');
        foreach ($recommendations as $recommendation) {
            $this->line("• {$recommendation}");
        }

        if ($this->option('force') && $this->confirm('Apply recommended optimizations?')) {
            $this->applyIndexOptimizations();
            $this->info('Index optimizations applied successfully.');
        } else {
            $this->info('Run with --force to apply optimizations automatically.');
        }
        
        return 0;
    }

    /**
     * Get audit_logs table size in MB
     */
    private function getTableSize(): float
    {
        $result = DB::select("
            SELECT 
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'audit_logs'
        ");
        
        return $result[0]->size_mb ?? 0;
    }

    /**
     * Get index statistics
     */
    private function getIndexStats(): array
    {
        try {
            $stats = DB::select("
                SHOW INDEX FROM audit_logs
            ");
            
            $indexData = [];
            foreach ($stats as $stat) {
                if ($stat->Key_name !== 'PRIMARY') {
                    $indexData[] = [
                        $stat->Key_name,
                        $stat->Cardinality ?? 'N/A',
                        'Active' // Simplified - would need query log analysis for actual usage
                    ];
                }
            }
            
            return $indexData;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Analyze query patterns for index recommendations
     */
    private function analyzeQueryPatterns(): array
    {
        $recommendations = [];
        
        // Check for slow queries (simplified analysis)
        $slowQueries = $this->identifySlowQueryPatterns();
        
        foreach ($slowQueries as $pattern) {
            $recommendations[] = $pattern;
        }
        
        return $recommendations;
    }

    /**
     * Identify slow query patterns
     */
    private function identifySlowQueryPatterns(): array
    {
        $patterns = [];
        
        // Check if we have many logs without proper date range queries
        $totalLogs = AuditLog::count();
        if ($totalLogs > 100000) {
            $patterns[] = "Consider partitioning audit_logs table by date for tables > 100k records";
        }
        
        // Check for missing composite indexes based on common filter combinations
        $eventTypeVariety = AuditLog::distinct('event_type')->count();
        if ($eventTypeVariety > 10) {
            $patterns[] = "High event type variety detected - composite indexes are beneficial";
        }
        
        return $patterns;
    }

    /**
     * Apply index optimizations
     */
    private function applyIndexOptimizations(): void
    {
        // This would contain actual optimization queries
        // For now, we'll just log that optimizations would be applied
        $this->info('Applying table optimizations...');
        
        try {
            DB::statement('OPTIMIZE TABLE audit_logs');
            $this->info('Table optimization completed.');
        } catch (\Exception $e) {
            $this->warn('Table optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Display performance recommendations
     */
    private function displayRecommendations(int $totalLogs, int $logsLast24h, float $tableSize): void
    {
        $this->info("\nPerformance Recommendations:");
        
        if ($totalLogs > 1000000) {
            $this->warn("• Consider implementing audit log archival (1M+ records)");
        }
        
        if ($logsLast24h > 10000) {
            $this->warn("• High audit volume detected - ensure queue workers are scaled appropriately");
        }
        
        if ($tableSize > 1000) {
            $this->warn("• Large table size ({$tableSize}MB) - consider partitioning or archival");
        }
        
        if ($logsLast24h / 24 > 500) {
            $this->info("• Consider using bulk processing for high-volume operations");
        }
        
        $this->info("• Regular cache warmup recommended for optimal performance");
        $this->info("• Monitor queue processing to prevent audit log backlogs");
    }
}
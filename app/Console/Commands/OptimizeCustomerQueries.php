<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Package;
use App\Models\Profile;

class OptimizeCustomerQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:optimize-queries 
                            {--analyze : Analyze current query performance}
                            {--indexes : Show current indexes}
                            {--suggestions : Show optimization suggestions}
                            {--test : Run test queries to measure performance}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize customer-related database queries and analyze performance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Customer Query Optimization Tool');
        $this->line('=====================================');

        if ($this->option('analyze')) {
            $this->analyzeQueryPerformance();
        }

        if ($this->option('indexes')) {
            $this->showCurrentIndexes();
        }

        if ($this->option('suggestions')) {
            $this->showOptimizationSuggestions();
        }

        if ($this->option('test')) {
            $this->runPerformanceTests();
        }

        if (!$this->option('analyze') && !$this->option('indexes') && 
            !$this->option('suggestions') && !$this->option('test')) {
            $this->showMenu();
        }

        return 0;
    }

    /**
     * Show interactive menu
     */
    private function showMenu()
    {
        $choice = $this->choice(
            'What would you like to do?',
            [
                'analyze' => 'Analyze query performance',
                'indexes' => 'Show current indexes',
                'suggestions' => 'Show optimization suggestions',
                'test' => 'Run performance tests',
                'all' => 'Run all analyses'
            ],
            'all'
        );

        switch ($choice) {
            case 'analyze':
                $this->analyzeQueryPerformance();
                break;
            case 'indexes':
                $this->showCurrentIndexes();
                break;
            case 'suggestions':
                $this->showOptimizationSuggestions();
                break;
            case 'test':
                $this->runPerformanceTests();
                break;
            case 'all':
                $this->analyzeQueryPerformance();
                $this->showCurrentIndexes();
                $this->showOptimizationSuggestions();
                $this->runPerformanceTests();
                break;
        }
    }

    /**
     * Analyze current query performance
     */
    private function analyzeQueryPerformance()
    {
        $this->info("\n📊 Query Performance Analysis");
        $this->line("==============================");

        // Get table statistics
        $this->showTableStatistics();

        // Analyze common customer queries
        $this->analyzeCommonQueries();
    }

    /**
     * Show table statistics
     */
    private function showTableStatistics()
    {
        $this->info("\n📈 Table Statistics:");

        $tables = ['users', 'profiles', 'packages', 'pre_alerts', 'purchase_requests'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line("  • {$table}: {$count} records");
            }
        }

        // Customer-specific statistics
        $customerCount = User::customers()->count();
        $activeCustomerCount = User::activeCustomers()->count();
        $deletedCustomerCount = User::deletedCustomers()->count();

        $this->line("\n👥 Customer Statistics:");
        $this->line("  • Total customers: {$customerCount}");
        $this->line("  • Active customers: {$activeCustomerCount}");
        $this->line("  • Deleted customers: {$deletedCustomerCount}");

        if (Schema::hasTable('packages')) {
            $packageCount = Package::count();
            $avgPackagesPerCustomer = $customerCount > 0 ? round($packageCount / $customerCount, 2) : 0;
            $this->line("  • Total packages: {$packageCount}");
            $this->line("  • Avg packages per customer: {$avgPackagesPerCustomer}");
        }
    }

    /**
     * Analyze common customer queries
     */
    private function analyzeCommonQueries()
    {
        $this->info("\n🔍 Common Query Analysis:");

        $queries = [
            'Customer List Query' => function() {
                return User::customers()->with('profile')->limit(10);
            },
            'Customer Search Query' => function() {
                return User::customers()->search('test')->with('profile')->limit(10);
            },
            'Customer with Packages' => function() {
                return User::customers()->withCount('packages')->with('profile')->limit(10);
            },
            'Package Statistics Query' => function() {
                if (Schema::hasTable('packages')) {
                    return Package::with('user.profile')->where('status', 'delivered')->limit(10);
                }
                return null;
            }
        ];

        foreach ($queries as $name => $queryBuilder) {
            $this->line("\n  📋 {$name}:");
            
            try {
                $query = $queryBuilder();
                if ($query) {
                    $sql = $query->toSql();
                    $this->line("     SQL: " . $this->truncateString($sql, 80));
                    
                    // Measure execution time
                    $start = microtime(true);
                    $results = $query->get();
                    $end = microtime(true);
                    
                    $executionTime = round(($end - $start) * 1000, 2);
                    $resultCount = $results->count();
                    
                    $this->line("     Results: {$resultCount} records");
                    $this->line("     Time: {$executionTime}ms");
                    
                    if ($executionTime > 100) {
                        $this->warn("     ⚠️  Slow query detected!");
                    } elseif ($executionTime > 50) {
                        $this->comment("     ⚡ Moderate performance");
                    } else {
                        $this->info("     ✅ Good performance");
                    }
                }
            } catch (\Exception $e) {
                $this->error("     ❌ Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Show current database indexes
     */
    private function showCurrentIndexes()
    {
        $this->info("\n🗂️  Current Database Indexes");
        $this->line("============================");

        $tables = ['users', 'profiles', 'packages', 'pre_alerts', 'purchase_requests'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("\n📋 {$table} table:");
                $this->showTableIndexes($table);
            }
        }
    }

    /**
     * Show indexes for a specific table
     */
    private function showTableIndexes($table)
    {
        try {
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $doctrineSchemaManager->listTableIndexes($table);

            if (empty($indexes)) {
                $this->line("  • No indexes found");
                return;
            }

            foreach ($indexes as $index) {
                $name = $index->getName();
                $columns = implode(', ', $index->getColumns());
                $unique = $index->isUnique() ? ' (UNIQUE)' : '';
                $primary = $index->isPrimary() ? ' (PRIMARY)' : '';
                
                $this->line("  • {$name}: [{$columns}]{$unique}{$primary}");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Could not retrieve indexes: " . $e->getMessage());
        }
    }

    /**
     * Show optimization suggestions
     */
    private function showOptimizationSuggestions()
    {
        $this->info("\n💡 Optimization Suggestions");
        $this->line("============================");

        $suggestions = $this->generateOptimizationSuggestions();

        foreach ($suggestions as $category => $items) {
            $this->line("\n📌 {$category}:");
            foreach ($items as $suggestion) {
                $this->line("  • {$suggestion}");
            }
        }
    }

    /**
     * Generate optimization suggestions based on current state
     */
    private function generateOptimizationSuggestions()
    {
        $suggestions = [];

        // Index suggestions
        $indexSuggestions = [];
        
        if (Schema::hasTable('packages')) {
            $packageCount = DB::table('packages')->count();
            if ($packageCount > 1000) {
                $indexSuggestions[] = "Add composite index on packages(user_id, status) for customer package filtering";
                $indexSuggestions[] = "Add index on packages(created_at) for date-based queries";
            }
        }

        if (Schema::hasTable('users')) {
            $userCount = DB::table('users')->count();
            if ($userCount > 500) {
                $indexSuggestions[] = "Add composite index on users(role_id, deleted_at) for customer filtering";
                $indexSuggestions[] = "Add index on users(email, deleted_at) for login optimization";
            }
        }

        if (!empty($indexSuggestions)) {
            $suggestions['Database Indexes'] = $indexSuggestions;
        }

        // Query optimization suggestions
        $querySuggestions = [
            "Use eager loading (with()) to reduce N+1 query problems",
            "Implement pagination for large result sets",
            "Use select() to limit columns when full models aren't needed",
            "Consider using database views for complex reporting queries",
            "Implement query result caching for frequently accessed data"
        ];
        $suggestions['Query Optimization'] = $querySuggestions;

        // Performance suggestions
        $performanceSuggestions = [
            "Monitor slow query log to identify bottlenecks",
            "Consider read replicas for reporting queries",
            "Implement connection pooling for high-traffic scenarios",
            "Use database-specific optimizations (e.g., MySQL query cache)",
            "Regular ANALYZE TABLE to update statistics"
        ];
        $suggestions['Performance Tuning'] = $performanceSuggestions;

        // Application-level suggestions
        $appSuggestions = [
            "Implement Redis caching for customer statistics",
            "Use background jobs for heavy calculations",
            "Implement API rate limiting to prevent abuse",
            "Consider using CDN for static assets",
            "Optimize Livewire component loading"
        ];
        $suggestions['Application Level'] = $appSuggestions;

        return $suggestions;
    }

    /**
     * Run performance tests
     */
    private function runPerformanceTests()
    {
        $this->info("\n🚀 Performance Tests");
        $this->line("====================");

        $tests = [
            'Customer List Performance' => function() {
                return $this->testCustomerListPerformance();
            },
            'Customer Search Performance' => function() {
                return $this->testCustomerSearchPerformance();
            },
            'Package Statistics Performance' => function() {
                return $this->testPackageStatisticsPerformance();
            },
            'Financial Calculations Performance' => function() {
                return $this->testFinancialCalculationsPerformance();
            }
        ];

        foreach ($tests as $testName => $testFunction) {
            $this->line("\n🧪 {$testName}:");
            
            try {
                $result = $testFunction();
                $this->displayTestResult($result);
            } catch (\Exception $e) {
                $this->error("  ❌ Test failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Test customer list performance
     */
    private function testCustomerListPerformance()
    {
        $iterations = 5;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            $customers = User::customers()
                ->with('profile')
                ->orderBy('id', 'desc')
                ->limit(50)
                ->get();
                
            $end = microtime(true);
            $times[] = ($end - $start) * 1000;
        }

        return [
            'iterations' => $iterations,
            'avg_time' => round(array_sum($times) / count($times), 2),
            'min_time' => round(min($times), 2),
            'max_time' => round(max($times), 2),
            'result_count' => $customers->count()
        ];
    }

    /**
     * Test customer search performance
     */
    private function testCustomerSearchPerformance()
    {
        $searchTerms = ['test', 'john', 'smith', 'admin'];
        $results = [];

        foreach ($searchTerms as $term) {
            $start = microtime(true);
            
            $customers = User::customers()
                ->search($term)
                ->with('profile')
                ->limit(20)
                ->get();
                
            $end = microtime(true);
            
            $results[] = [
                'term' => $term,
                'time' => round(($end - $start) * 1000, 2),
                'results' => $customers->count()
            ];
        }

        return $results;
    }

    /**
     * Test package statistics performance
     */
    private function testPackageStatisticsPerformance()
    {
        if (!Schema::hasTable('packages')) {
            return ['error' => 'Packages table not found'];
        }

        $start = microtime(true);
        
        $stats = DB::table('packages')
            ->select(
                DB::raw('COUNT(*) as total_packages'),
                DB::raw('COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered'),
                DB::raw('COUNT(CASE WHEN status = "in_transit" THEN 1 END) as in_transit'),
                DB::raw('AVG(freight_price) as avg_freight'),
                DB::raw('SUM(freight_price) as total_freight')
            )
            ->first();
            
        $end = microtime(true);

        return [
            'time' => round(($end - $start) * 1000, 2),
            'stats' => $stats
        ];
    }

    /**
     * Test financial calculations performance
     */
    private function testFinancialCalculationsPerformance()
    {
        if (!Schema::hasTable('packages')) {
            return ['error' => 'Packages table not found'];
        }

        $customerIds = User::customers()->limit(10)->pluck('id');
        $results = [];

        foreach ($customerIds as $customerId) {
            $start = microtime(true);
            
            $financial = DB::table('packages')
                ->where('user_id', $customerId)
                ->select(
                    DB::raw('SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total_spent'),
                    DB::raw('COUNT(*) as package_count'),
                    DB::raw('AVG(freight_price + customs_duty + storage_fee + delivery_fee) as avg_cost')
                )
                ->first();
                
            $end = microtime(true);
            
            $results[] = [
                'customer_id' => $customerId,
                'time' => round(($end - $start) * 1000, 2),
                'total_spent' => $financial->total_spent ?? 0
            ];
        }

        return $results;
    }

    /**
     * Display test result
     */
    private function displayTestResult($result)
    {
        if (isset($result['error'])) {
            $this->error("  ❌ " . $result['error']);
            return;
        }

        if (isset($result['iterations'])) {
            // Single test result
            $this->line("  📊 Iterations: {$result['iterations']}");
            $this->line("  ⏱️  Average time: {$result['avg_time']}ms");
            $this->line("  📈 Min/Max: {$result['min_time']}ms / {$result['max_time']}ms");
            
            if (isset($result['result_count'])) {
                $this->line("  📋 Results: {$result['result_count']} records");
            }
            
            // Performance assessment
            if ($result['avg_time'] > 100) {
                $this->warn("  ⚠️  Performance needs improvement");
            } elseif ($result['avg_time'] > 50) {
                $this->comment("  ⚡ Moderate performance");
            } else {
                $this->info("  ✅ Good performance");
            }
        } elseif (is_array($result) && isset($result[0]['term'])) {
            // Search test results
            foreach ($result as $searchResult) {
                $this->line("  🔍 '{$searchResult['term']}': {$searchResult['time']}ms ({$searchResult['results']} results)");
            }
        } elseif (is_array($result) && isset($result[0]['customer_id'])) {
            // Financial test results
            $avgTime = round(array_sum(array_column($result, 'time')) / count($result), 2);
            $this->line("  💰 Financial calculations: {$avgTime}ms average");
            $this->line("  👥 Tested {count($result)} customers");
        } else {
            // Generic result
            if (isset($result['time'])) {
                $this->line("  ⏱️  Execution time: {$result['time']}ms");
            }
        }
    }

    /**
     * Truncate string for display
     */
    private function truncateString($string, $length)
    {
        return strlen($string) > $length ? substr($string, 0, $length) . '...' : $string;
    }
}
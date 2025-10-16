<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BusinessReportService;
use App\Services\ReportMonitoringService;
use Carbon\Carbon;

class TestReportsSystem extends Command
{
    protected $signature = 'reports:test-system';
    protected $description = 'Test the reports system to ensure it works correctly';

    public function handle()
    {
        $this->info('Testing Reports System...');
        
        // Test 1: Check cache directories
        $this->info('1. Checking cache directories...');
        $this->call('cache:fix-directories');
        
        // Test 2: Test monitoring service
        $this->info('2. Testing monitoring service...');
        try {
            $monitoringService = app(ReportMonitoringService::class);
            $health = $monitoringService->performHealthCheck();
            $this->info("System health: {$health['overall_status']}");
        } catch (\Exception $e) {
            $this->error("Monitoring service error: {$e->getMessage()}");
        }
        
        // Test 3: Test report generation
        $this->info('3. Testing report generation...');
        try {
            $businessService = app(BusinessReportService::class);
            
            $filters = [
                'date_from' => Carbon::now()->subDays(30),
                'date_to' => Carbon::now()
            ];
            
            $result = $businessService->generateSalesCollectionsReport($filters);
            
            if (isset($result['success']) && $result['success']) {
                $this->info('✓ Sales report generated successfully');
            } else {
                $this->warn('⚠ Sales report returned error response');
                if (isset($result['message'])) {
                    $this->warn("Message: {$result['message']}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Report generation error: {$e->getMessage()}");
        }
        
        // Test 4: Clear any problematic cache
        $this->info('4. Clearing cache...');
        $this->call('cache:clear');
        $this->call('config:cache');
        
        $this->info('Reports system test completed!');
        $this->comment('If you see any errors above, please address them before using the reports.');
        
        return 0;
    }
}
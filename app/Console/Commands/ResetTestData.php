<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:test-data {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and recreate comprehensive test data for package distribution system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Package Distribution Test Data Reset');
        $this->info('=====================================');
        $this->info('');
        
        if (!$this->option('confirm')) {
            $this->warn('⚠️  This will clear existing package and distribution data!');
            $this->info('The following data will be cleared:');
            $this->info('  • All packages and distributions');
            $this->info('  • All customer transactions');
            $this->info('  • Customer account balances');
            $this->info('');
            $this->info('The following data will be preserved:');
            $this->info('  • User accounts and profiles');
            $this->info('  • Manifests, offices, shippers');
            $this->info('  • Roles and permissions');
            $this->info('');
            
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('🚀 Starting comprehensive test data reset...');
        $this->info('');

        try {
            // Run the comprehensive test data seeder
            $this->info('📊 Running ComprehensiveTestDataSeeder...');
            Artisan::call('db:seed', [
                '--class' => 'ComprehensiveTestDataSeeder',
                '--force' => true
            ]);

            $this->info('✅ Test data reset completed successfully!');
            $this->info('');
            
            // Display summary
            $this->displaySummary();
            
            // Show useful commands
            $this->displayUsefulCommands();

        } catch (\Exception $e) {
            $this->error('❌ Error during test data reset: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Display summary of created data
     */
    private function displaySummary(): void
    {
        $this->info('📋 Test Data Summary:');
        $this->info('====================');
        
        try {
            $customerCount = \App\Models\User::whereHas('role', function($q) {
                $q->where('name', 'customer');
            })->count();
            
            $packageCount = \App\Models\Package::count();
            $readyPackages = \App\Models\Package::where('status', 'ready')->count();
            $distributionCount = \App\Models\PackageDistribution::count();
            $transactionCount = \App\Models\CustomerTransaction::count();
            
            $this->info("👥 Customers: {$customerCount}");
            $this->info("📦 Total Packages: {$packageCount}");
            $this->info("✅ Ready for Pickup: {$readyPackages}");
            $this->info("📊 Distributions: {$distributionCount}");
            $this->info("💰 Transactions: {$transactionCount}");
            
        } catch (\Exception $e) {
            $this->warn('Could not generate summary: ' . $e->getMessage());
        }
        
        $this->info('');
    }

    /**
     * Display useful commands for testing
     */
    private function displayUsefulCommands(): void
    {
        $this->info('🛠️  Useful Testing Commands:');
        $this->info('===========================');
        $this->info('');
        
        $this->info('📦 Package Distribution Demo:');
        $this->info('  php artisan demo:package-distribution "John NewCustomer" --amount=30');
        $this->info('  php artisan demo:package-distribution "Sarah PositiveBalance" --amount=50 --apply-credit');
        $this->info('  php artisan demo:package-distribution "Mike CreditOnly" --amount=80 --apply-credit');
        $this->info('');
        
        $this->info('🧪 Run Tests:');
        $this->info('  php artisan test tests/Unit/PackageDistributionBalanceTest.php');
        $this->info('  php artisan test tests/Unit/PackageDistributionOverpaymentTest.php');
        $this->info('  php artisan test tests/Unit/CustomerAccountBalanceTest.php');
        $this->info('');
        
        $this->info('🔍 Check Customer Balances:');
        $this->info('  php artisan tinker --execute="\\App\\Models\\User::where(\'first_name\', \'Sarah\')->first()->getAccountBalanceSummary()"');
        $this->info('');
        
        $this->info('📊 View Dashboard:');
        $this->info('  Visit: /dashboard (login as any customer to see balance display)');
        $this->info('');
        
        $this->info('⚙️ Package Workflow:');
        $this->info('  Visit: /admin/manifests/{id}/packages (to test fee entry modal)');
        $this->info('');
        
        $this->info('🔄 Reset Again:');
        $this->info('  php artisan reset:test-data --confirm');
    }
}
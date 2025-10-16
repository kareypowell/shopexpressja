<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixTransactionViewCache extends Command
{
    protected $signature = 'fix:transaction-view-cache';
    protected $description = 'Fix transaction management view caching issues';

    public function handle()
    {
        $this->info('Fixing transaction management view cache issues...');

        // Step 1: Clear all caches
        $this->info('1. Clearing Laravel caches...');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        // Step 2: Clear compiled views manually
        $this->info('2. Clearing compiled views...');
        $compiledViewsPath = storage_path('framework/views');
        if (File::exists($compiledViewsPath)) {
            File::cleanDirectory($compiledViewsPath);
            $this->info('   ✓ Compiled views cleared');
        }

        // Step 3: Check if view file exists
        $this->info('3. Checking view file...');
        $viewPath = resource_path('views/livewire/transaction-management.blade.php');
        if (File::exists($viewPath)) {
            $this->info('   ✓ View file exists: ' . $viewPath);
            
            // Check if manifest filter is in the file
            $content = File::get($viewPath);
            if (strpos($content, 'selectedManifestId') !== false) {
                $this->info('   ✓ Manifest filter code found in view');
            } else {
                $this->error('   ✗ Manifest filter code NOT found in view');
            }
            
            if (strpos($content, 'Manifest') !== false) {
                $this->info('   ✓ Manifest column code found in view');
            } else {
                $this->error('   ✗ Manifest column code NOT found in view');
            }
        } else {
            $this->error('   ✗ View file not found: ' . $viewPath);
        }

        // Step 4: Check Livewire component
        $this->info('4. Checking Livewire component...');
        $componentPath = app_path('Http/Livewire/TransactionManagement.php');
        if (File::exists($componentPath)) {
            $this->info('   ✓ Component file exists: ' . $componentPath);
            
            $content = File::get($componentPath);
            if (strpos($content, 'selectedManifestId') !== false) {
                $this->info('   ✓ Manifest filter property found in component');
            } else {
                $this->error('   ✗ Manifest filter property NOT found in component');
            }
            
            if (strpos($content, 'getManifestsProperty') !== false) {
                $this->info('   ✓ Manifests property method found in component');
            } else {
                $this->error('   ✗ Manifests property method NOT found in component');
            }
        } else {
            $this->error('   ✗ Component file not found: ' . $componentPath);
        }

        // Step 5: Recompile views
        $this->info('5. Recompiling views...');
        $this->call('view:cache');

        // Step 6: Test data check
        $this->info('6. Checking data availability...');
        try {
            $manifestCount = \App\Models\Manifest::count();
            $transactionCount = \App\Models\CustomerTransaction::count();
            
            $this->info("   Manifests in database: {$manifestCount}");
            $this->info("   Transactions in database: {$transactionCount}");
            
            if ($manifestCount > 0) {
                $this->info('   ✓ Manifests available for filter dropdown');
            } else {
                $this->warn('   ⚠ No manifests found - filter dropdown will be empty');
            }
            
            if ($transactionCount > 0) {
                $linkedCount = \App\Models\CustomerTransaction::whereNotNull('manifest_id')->count();
                $this->info("   Transactions linked to manifests: {$linkedCount}");
                
                if ($linkedCount > 0) {
                    $this->info('   ✓ Some transactions are linked to manifests');
                } else {
                    $this->warn('   ⚠ No transactions are linked to manifests');
                }
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Error checking data: ' . $e->getMessage());
        }

        $this->info('');
        $this->info('✅ Cache clearing completed!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('1. Hard refresh your browser (Ctrl+F5 or Cmd+Shift+R)');
        $this->info('2. Go to Transaction Management page');
        $this->info('3. Look for:');
        $this->info('   - Manifest filter dropdown in the filters section');
        $this->info('   - Manifest column in the transactions table');
        $this->info('');
        
        if ($manifestCount > 0) {
            $this->info('You should now see the manifest filter and column!');
        } else {
            $this->warn('Create some manifests first to see the filter options.');
        }

        return 0;
    }
}
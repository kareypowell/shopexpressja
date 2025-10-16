<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Manifest;
use App\Models\CustomerTransaction;

class TestManifestFilter extends Command
{
    protected $signature = 'test:manifest-filter';
    protected $description = 'Test manifest filter functionality';

    public function handle()
    {
        $this->info('Testing Manifest Filter Functionality...');
        
        // Check manifests
        $manifestCount = Manifest::count();
        $this->info("Manifests in database: {$manifestCount}");
        
        if ($manifestCount > 0) {
            $manifests = Manifest::take(5)->get();
            $this->info('Available manifests:');
            foreach ($manifests as $manifest) {
                $this->info("  - {$manifest->name} ({$manifest->type})");
            }
        } else {
            $this->warn('No manifests found - filter dropdown will be empty');
        }
        
        // Check transactions
        $transactionCount = CustomerTransaction::count();
        $this->info("Transactions in database: {$transactionCount}");
        
        if ($transactionCount > 0) {
            $linkedCount = CustomerTransaction::whereNotNull('manifest_id')->count();
            $this->info("Transactions linked to manifests: {$linkedCount}");
            
            if ($linkedCount > 0) {
                $linkedTransactions = CustomerTransaction::with('manifest')
                    ->whereNotNull('manifest_id')
                    ->take(3)
                    ->get();
                    
                $this->info('Sample linked transactions:');
                foreach ($linkedTransactions as $transaction) {
                    $manifestName = $transaction->manifest ? $transaction->manifest->name : 'Unknown';
                    $this->info("  - Transaction #{$transaction->id} → Manifest: {$manifestName}");
                }
            } else {
                $this->warn('No transactions are linked to manifests');
                $this->info('To link transactions to manifests:');
                $this->info('1. Create transactions through the Transaction Management interface');
                $this->info('2. Select a manifest when creating the transaction');
                $this->info('3. Or process packages through distribution (auto-links)');
            }
        } else {
            $this->warn('No transactions found');
        }
        
        $this->info('');
        $this->info('✅ Manifest filter should now be visible in the first row of filters');
        $this->info('Layout: [Search] [Type] [Customer] [Manifest] [Review Status]');
        $this->info('');
        
        if ($manifestCount > 0) {
            $this->info('🎯 The manifest dropdown should show your available manifests');
        } else {
            $this->warn('⚠️  Create some manifests first to see options in the dropdown');
        }
        
        return 0;
    }
}
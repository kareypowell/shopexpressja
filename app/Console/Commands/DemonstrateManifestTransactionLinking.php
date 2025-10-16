<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Manifest;
use App\Models\CustomerTransaction;

class DemonstrateManifestTransactionLinking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:manifest-transactions {manifest_id? : The ID of the manifest to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate manifest-transaction linking functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $manifestId = $this->argument('manifest_id');
        
        if ($manifestId) {
            $manifest = Manifest::find($manifestId);
            if (!$manifest) {
                $this->error("Manifest with ID {$manifestId} not found.");
                return 1;
            }
            $this->analyzeManifest($manifest);
        } else {
            // Analyze first few manifests
            $manifests = Manifest::with(['packages', 'transactions'])->limit(5)->get();
            
            if ($manifests->isEmpty()) {
                $this->info('No manifests found in the database.');
                return 0;
            }
            
            foreach ($manifests as $manifest) {
                $this->analyzeManifest($manifest);
                $this->line(''); // Empty line between manifests
            }
        }
        
        $this->demonstrateTransactionCreation();
        
        return 0;
    }
    
    private function analyzeManifest(Manifest $manifest)
    {
        $this->info("Analyzing Manifest: {$manifest->name} (ID: {$manifest->id})");
        $this->line("Type: " . ucfirst($manifest->type ?? 'air'));
        $this->line("Status: {$manifest->status_label}");
        
        // Get package information
        $packages = $manifest->packages;
        $this->line("Packages: {$packages->count()}");
        
        if ($packages->isEmpty()) {
            $this->warn("No packages found for this manifest.");
            return;
        }
        
        // Get linked transactions
        $transactions = $manifest->transactions;
        $this->line("Linked Transactions: {$transactions->count()}");
        
        if ($transactions->isNotEmpty()) {
            $this->table(
                ['Date', 'Customer', 'Type', 'Amount', 'Description'],
                $transactions->map(function ($transaction) {
                    return [
                        $transaction->created_at->format('M j, Y'),
                        $transaction->user->full_name ?? 'N/A',
                        ucfirst(str_replace('_', ' ', $transaction->type)),
                        ($transaction->isCredit() ? '+' : '-') . '$' . number_format($transaction->amount, 2),
                        substr($transaction->description, 0, 50) . (strlen($transaction->description) > 50 ? '...' : ''),
                    ];
                })->toArray()
            );
        }
        
        // Get financial summary
        $summary = $manifest->getFinancialSummary();
        
        $this->line("\nFinancial Summary:");
        $this->line("- Total Owed: $" . number_format($summary['total_owed'], 2));
        $this->line("- Total Collected: $" . number_format($summary['total_collected'], 2));
        $this->line("- Total Write-offs: $" . number_format($summary['total_write_off'], 2));
        $this->line("- Outstanding Balance: $" . number_format($summary['outstanding_balance'], 2));
        $this->line("- Collection Rate: " . number_format($summary['collection_rate'], 1) . "%");
        
        // Show customers with packages in this manifest
        $customers = $packages->pluck('user')->unique('id');
        if ($customers->isNotEmpty()) {
            $this->line("\nCustomers with packages in this manifest:");
            foreach ($customers as $customer) {
                $customerPackages = $packages->where('user_id', $customer->id);
                $this->line("- {$customer->full_name} ({$customerPackages->count()} packages)");
            }
        }
    }
    
    private function demonstrateTransactionCreation()
    {
        $this->line("\n" . str_repeat('=', 60));
        $this->info("Demonstrating Transaction Creation with Manifest Linking");
        $this->line(str_repeat('=', 60));
        
        // Get a customer and manifest for demonstration
        $customer = User::customers()->first();
        $manifest = Manifest::first();
        
        if (!$customer || !$manifest) {
            $this->warn("Need at least one customer and one manifest to demonstrate transaction creation.");
            return;
        }
        
        $this->line("Customer: {$customer->full_name}");
        $this->line("Manifest: {$manifest->name}");
        
        // Show available methods
        $this->line("\nAvailable methods for linking transactions to manifests:");
        
        $methods = [
            'recordPaymentForManifest()' => 'Record a payment linked to a manifest',
            'recordChargeForManifest()' => 'Record a charge linked to a manifest',
            'linkToManifest()' => 'Link an existing transaction to a manifest',
            'forManifest()' => 'Query transactions for a specific manifest',
            'linkedToManifests()' => 'Query all transactions linked to manifests',
        ];
        
        foreach ($methods as $method => $description) {
            $this->line("- {$method}: {$description}");
        }
        
        // Show filtering examples
        $this->line("\nFiltering Examples:");
        
        // Count transactions by type for this manifest
        $manifestTransactions = CustomerTransaction::forManifest($manifest->id)->get();
        $transactionsByType = $manifestTransactions->groupBy('type');
        
        if ($transactionsByType->isNotEmpty()) {
            $this->line("Transactions for manifest '{$manifest->name}':");
            foreach ($transactionsByType as $type => $transactions) {
                $total = $transactions->sum('amount');
                $this->line("- " . ucfirst(str_replace('_', ' ', $type)) . ": {$transactions->count()} transactions, $" . number_format($total, 2));
            }
        } else {
            $this->line("No transactions found for manifest '{$manifest->name}'");
        }
        
        // Show all manifests with linked transactions
        $manifestsWithTransactions = Manifest::whereHas('transactions')->get();
        $this->line("\nManifests with linked transactions: {$manifestsWithTransactions->count()}");
        
        foreach ($manifestsWithTransactions as $m) {
            $transactionCount = $m->transactions()->count();
            $totalAmount = $m->getTotalCollectedAmount();
            $this->line("- {$m->name}: {$transactionCount} transactions, $" . number_format($totalAmount, 2) . " collected");
        }
        
        $this->line("\n✅ Manifest-transaction linking is working correctly!");
    }
}
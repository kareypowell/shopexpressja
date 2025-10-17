<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomerTransaction;
use App\Models\Package;
use App\Models\PackageDistribution;
use App\Models\Manifest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillTransactionManifestLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backfill:transaction-manifest-links 
                            {--dry-run : Show what would be updated without making changes}
                            {--batch-size=100 : Number of transactions to process per batch}
                            {--force : Skip confirmation prompt}
                            {--v|verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill existing transactions with manifest links based on package/distribution references';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        $this->info('Starting transaction-manifest backfill process...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get transactions that could be linked to manifests
        $candidateTransactions = $this->getCandidateTransactions();
        
        if ($candidateTransactions->isEmpty()) {
            $this->info('No transactions found that can be linked to manifests.');
            return 0;
        }

        $this->info("Found {$candidateTransactions->count()} transactions that could be linked to manifests.");

        // Analyze what would be updated
        $analysis = $this->analyzeTransactions($candidateTransactions);
        $this->displayAnalysis($analysis);

        if (!$dryRun && !$force) {
            if (!$this->confirm('Do you want to proceed with linking these transactions to manifests?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Process transactions
        $updated = 0;
        $errors = 0;
        
        foreach ($candidateTransactions as $transaction) {
            try {
                $result = $this->processTransaction($transaction, $dryRun);
                if ($result) {
                    $updated++;
                    if (!$dryRun) {
                        $this->line("✓ Updated transaction #{$transaction->id}");
                    } else {
                        $this->line("✓ Would update transaction #{$transaction->id}");
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error processing transaction #{$transaction->id}: " . $e->getMessage());
            }
        }

        $this->line('');
        if ($dryRun) {
            $this->info("DRY RUN COMPLETE:");
            $this->info("- Would update: {$updated} transactions");
        } else {
            $this->info("BACKFILL COMPLETE:");
            $this->info("- Updated: {$updated} transactions");
        }
        
        if ($errors > 0) {
            $this->warn("- Errors: {$errors} transactions");
        }

        return 0;
    }

    /**
     * Get transactions that could potentially be linked to manifests
     */
    private function getCandidateTransactions()
    {
        // Check if manifest_id column exists
        if (!Schema::hasColumn('customer_transactions', 'manifest_id')) {
            $this->error("manifest_id column does not exist in customer_transactions table.");
            $this->error("Please run: php artisan migrate");
            return collect([]);
        }

        return CustomerTransaction::whereNull('manifest_id')
        ->where(function ($query) {
            // Have references that could lead to manifests
            $query->where('reference_type', 'App\\Models\\Package')
                  ->orWhere('reference_type', 'App\\Models\\PackageDistribution')
                  ->orWhere('reference_type', 'package_distribution')
                  ->orWhere('reference_type', 'consolidated_package_distribution')
                  ->orWhere('reference_type', 'package')
                  ->orWhereRaw('JSON_EXTRACT(metadata, "$.package_ids") IS NOT NULL')
                  ->orWhereRaw('JSON_EXTRACT(metadata, "$.distribution_id") IS NOT NULL');
        })
        ->with(['user'])
        ->get();
    }

    /**
     * Analyze what transactions would be updated
     */
    private function analyzeTransactions($transactions)
    {
        $analysis = [
            'by_reference_type' => [],
            'by_manifest' => [],
            'total_amount' => 0,
            'customers_affected' => [],
        ];

        foreach ($transactions as $transaction) {
            $manifestId = $this->findManifestForTransaction($transaction);
            
            if ($manifestId) {
                // Count by reference type
                $refType = $transaction->reference_type ?: 'null';
                $analysis['by_reference_type'][$refType] = ($analysis['by_reference_type'][$refType] ?? 0) + 1;
                
                // Count by manifest
                $analysis['by_manifest'][$manifestId] = ($analysis['by_manifest'][$manifestId] ?? 0) + 1;
                
                // Track total amount
                $analysis['total_amount'] += $transaction->amount;
                
                // Track customers
                $analysis['customers_affected'][$transaction->user_id] = $transaction->user->full_name ?? 'Unknown';
            }
        }

        return $analysis;
    }

    /**
     * Display analysis results
     */
    private function displayAnalysis($analysis)
    {
        $this->line('');
        $this->info('ANALYSIS RESULTS:');
        
        $this->line('Transactions by current reference type:');
        foreach ($analysis['by_reference_type'] as $type => $count) {
            $this->line("  - {$type}: {$count} transactions");
        }
        
        $this->line('');
        $this->line('Transactions by target manifest:');
        foreach ($analysis['by_manifest'] as $manifestId => $count) {
            $manifest = Manifest::find($manifestId);
            $manifestName = $manifest ? $manifest->name : "Manifest #{$manifestId}";
            $this->line("  - {$manifestName}: {$count} transactions");
        }
        
        $this->line('');
        $this->line("Total transaction amount: $" . number_format($analysis['total_amount'], 2));
        $this->line("Customers affected: " . count($analysis['customers_affected']));
        
        if (count($analysis['customers_affected']) <= 10) {
            foreach ($analysis['customers_affected'] as $customerId => $customerName) {
                $this->line("  - {$customerName}");
            }
        } else {
            $this->line("  (Too many to list - " . count($analysis['customers_affected']) . " customers)");
        }
    }

    /**
     * Process a single transaction
     */
    private function processTransaction(CustomerTransaction $transaction, bool $dryRun = false): bool
    {
        $manifestId = $this->findManifestForTransaction($transaction);
        
        if (!$manifestId) {
            if ($this->option('verbose')) {
                $this->warn("  No manifest found for transaction #{$transaction->id}");
            }
            return false;
        }

        $manifest = Manifest::find($manifestId);
        if (!$manifest) {
            throw new \Exception("Manifest #{$manifestId} not found");
        }

        if ($dryRun) {
            return true; // Would update
        }

        // Check if manifest_id column exists
        if (!Schema::hasColumn('customer_transactions', 'manifest_id')) {
            throw new \Exception("manifest_id column does not exist in customer_transactions table. Please run the migration first.");
        }

        // Update only the manifest_id column, preserving original reference_type and reference_id
        $updated = $transaction->update([
            'manifest_id' => $manifestId,
        ]);

        if (!$updated) {
            if ($this->option('verbose')) {
                $this->warn("  Failed to update transaction #{$transaction->id}");
            }
            return false;
        }

        // Update metadata to track backfill information
        $metadata = $transaction->metadata ?? [];
        if (!is_array($metadata)) {
            $metadata = [];
        }
        
        $metadata['backfill_info'] = [
            'backfilled_at' => now()->toISOString(),
            'manifest_id' => $manifestId,
            'manifest_name' => $manifest->name,
        ];

        $transaction->update(['metadata' => $metadata]);

        return true;
    }

    /**
     * Find the manifest ID for a given transaction
     */
    private function findManifestForTransaction(CustomerTransaction $transaction): ?int
    {
        // Method 1: Direct package reference
        if ($transaction->reference_type === 'App\\Models\\Package' && $transaction->reference_id) {
            $package = Package::find($transaction->reference_id);
            if ($package && $package->manifest_id) {
                return $package->manifest_id;
            }
        }

        // Method 2: Package distribution reference
        if (in_array($transaction->reference_type, ['App\\Models\\PackageDistribution', 'package_distribution', 'consolidated_package_distribution']) && $transaction->reference_id) {
            $distribution = PackageDistribution::find($transaction->reference_id);
            if ($distribution) {
                // Get manifest from distribution packages
                $manifestIds = $distribution->packages()->pluck('manifest_id')->filter()->unique();
                if ($manifestIds->count() === 1) {
                    return $manifestIds->first();
                } elseif ($manifestIds->count() > 1) {
                    // Multiple manifests - use the most common one
                    $manifestCounts = $distribution->packages()
                        ->select('manifest_id', DB::raw('count(*) as count'))
                        ->whereNotNull('manifest_id')
                        ->groupBy('manifest_id')
                        ->orderBy('count', 'desc')
                        ->first();
                    
                    if ($manifestCounts) {
                        return $manifestCounts->manifest_id;
                    }
                }
            }
        }

        // Method 3: Metadata package IDs
        if ($transaction->metadata && isset($transaction->metadata['package_ids'])) {
            $packageIds = $transaction->metadata['package_ids'];
            if (is_array($packageIds) && !empty($packageIds)) {
                $manifestIds = Package::whereIn('id', $packageIds)
                    ->pluck('manifest_id')
                    ->filter()
                    ->unique();
                
                if ($manifestIds->count() === 1) {
                    return $manifestIds->first();
                } elseif ($manifestIds->count() > 1) {
                    // Multiple manifests - use the most common one
                    $manifestCounts = Package::whereIn('id', $packageIds)
                        ->select('manifest_id', DB::raw('count(*) as count'))
                        ->whereNotNull('manifest_id')
                        ->groupBy('manifest_id')
                        ->orderBy('count', 'desc')
                        ->first();
                    
                    if ($manifestCounts) {
                        return $manifestCounts->manifest_id;
                    }
                }
            }
        }

        // Method 4: Metadata distribution ID
        if ($transaction->metadata && isset($transaction->metadata['distribution_id'])) {
            $distributionId = $transaction->metadata['distribution_id'];
            $distribution = PackageDistribution::find($distributionId);
            if ($distribution) {
                $manifestIds = $distribution->packages()->pluck('manifest_id')->filter()->unique();
                if ($manifestIds->count() === 1) {
                    return $manifestIds->first();
                } elseif ($manifestIds->count() > 1) {
                    // Multiple manifests - use the most common one
                    $manifestCounts = $distribution->packages()
                        ->select('manifest_id', DB::raw('count(*) as count'))
                        ->whereNotNull('manifest_id')
                        ->groupBy('manifest_id')
                        ->orderBy('count', 'desc')
                        ->first();
                    
                    if ($manifestCounts) {
                        return $manifestCounts->manifest_id;
                    }
                }
            }
        }

        // Method 5: Metadata manifest IDs (if already stored)
        if ($transaction->metadata && isset($transaction->metadata['manifest_ids'])) {
            $manifestIds = $transaction->metadata['manifest_ids'];
            if (is_array($manifestIds) && count($manifestIds) === 1) {
                return $manifestIds[0];
            }
        }

        return null;
    }
}
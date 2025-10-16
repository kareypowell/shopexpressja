<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomerTransaction;
use App\Models\Package;
use App\Models\PackageDistribution;
use App\Models\Manifest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyzeTransactionManifestLinking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:transaction-manifest-links 
                            {--detailed : Show detailed breakdown by customer and manifest}
                            {--export=? : Export results to CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze current state of transaction-manifest linking and identify opportunities for backfilling';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $detailed = $this->option('detailed');
        $exportFile = $this->option('export');

        $this->info('Analyzing transaction-manifest linking status...');
        $this->line('');

        // Get overall statistics
        $stats = $this->getOverallStatistics();
        $this->displayOverallStats($stats);

        // Analyze linkable transactions
        $linkableAnalysis = $this->analyzeLinkableTransactions();
        $this->displayLinkableAnalysis($linkableAnalysis);

        // Show manifest coverage
        $manifestCoverage = $this->analyzeManifestCoverage();
        $this->displayManifestCoverage($manifestCoverage);

        if ($detailed) {
            $this->displayDetailedBreakdown();
        }

        if ($exportFile) {
            $this->exportToCSV($exportFile, $stats, $linkableAnalysis, $manifestCoverage);
        }

        $this->displayRecommendations($linkableAnalysis);

        return 0;
    }

    /**
     * Get overall transaction statistics
     */
    private function getOverallStatistics(): array
    {
        $totalTransactions = CustomerTransaction::count();
        $linkedToManifests = CustomerTransaction::where('reference_type', 'App\\Models\\Manifest')->count();
        $linkedToPackages = CustomerTransaction::where('reference_type', 'App\\Models\\Package')->count();
        $linkedToDistributions = CustomerTransaction::whereIn('reference_type', [
            'App\\Models\\PackageDistribution',
            'package_distribution',
            'consolidated_package_distribution'
        ])->count();
        $unlinked = CustomerTransaction::whereNull('reference_type')->count();

        $totalAmount = CustomerTransaction::sum('amount');
        $manifestLinkedAmount = CustomerTransaction::where('reference_type', 'App\\Models\\Manifest')->sum('amount');

        return [
            'total_transactions' => $totalTransactions,
            'linked_to_manifests' => $linkedToManifests,
            'linked_to_packages' => $linkedToPackages,
            'linked_to_distributions' => $linkedToDistributions,
            'unlinked' => $unlinked,
            'total_amount' => $totalAmount,
            'manifest_linked_amount' => $manifestLinkedAmount,
            'manifest_link_percentage' => $totalTransactions > 0 ? ($linkedToManifests / $totalTransactions) * 100 : 0,
            'manifest_amount_percentage' => $totalAmount > 0 ? ($manifestLinkedAmount / $totalAmount) * 100 : 0,
        ];
    }

    /**
     * Display overall statistics
     */
    private function displayOverallStats(array $stats): void
    {
        $this->info('OVERALL TRANSACTION STATISTICS:');
        $this->table(
            ['Metric', 'Count', 'Percentage', 'Amount'],
            [
                [
                    'Total Transactions',
                    number_format($stats['total_transactions']),
                    '100.0%',
                    '$' . number_format($stats['total_amount'], 2)
                ],
                [
                    'Linked to Manifests',
                    number_format($stats['linked_to_manifests']),
                    number_format($stats['manifest_link_percentage'], 1) . '%',
                    '$' . number_format($stats['manifest_linked_amount'], 2)
                ],
                [
                    'Linked to Packages',
                    number_format($stats['linked_to_packages']),
                    $stats['total_transactions'] > 0 ? number_format(($stats['linked_to_packages'] / $stats['total_transactions']) * 100, 1) . '%' : '0%',
                    '-'
                ],
                [
                    'Linked to Distributions',
                    number_format($stats['linked_to_distributions']),
                    $stats['total_transactions'] > 0 ? number_format(($stats['linked_to_distributions'] / $stats['total_transactions']) * 100, 1) . '%' : '0%',
                    '-'
                ],
                [
                    'Unlinked',
                    number_format($stats['unlinked']),
                    $stats['total_transactions'] > 0 ? number_format(($stats['unlinked'] / $stats['total_transactions']) * 100, 1) . '%' : '0%',
                    '-'
                ],
            ]
        );
        $this->line('');
    }

    /**
     * Analyze transactions that could be linked to manifests
     */
    private function analyzeLinkableTransactions(): array
    {
        $linkableTransactions = CustomerTransaction::where(function ($query) {
            $query->where('reference_type', '!=', 'App\\Models\\Manifest')
                  ->orWhereNull('reference_type');
        })
        ->get();

        $analysis = [
            'total_linkable' => 0,
            'by_type' => [],
            'by_customer' => [],
            'by_potential_manifest' => [],
            'total_amount' => 0,
            'examples' => [],
        ];

        foreach ($linkableTransactions as $transaction) {
            $manifestId = $this->findPotentialManifest($transaction);
            
            if ($manifestId) {
                $analysis['total_linkable']++;
                $analysis['total_amount'] += $transaction->amount;
                
                // Group by transaction type
                $type = $transaction->type;
                $analysis['by_type'][$type] = ($analysis['by_type'][$type] ?? 0) + 1;
                
                // Group by customer
                $customerId = $transaction->user_id;
                if (!isset($analysis['by_customer'][$customerId])) {
                    $analysis['by_customer'][$customerId] = [
                        'name' => $transaction->user->full_name ?? 'Unknown',
                        'count' => 0,
                        'amount' => 0,
                    ];
                }
                $analysis['by_customer'][$customerId]['count']++;
                $analysis['by_customer'][$customerId]['amount'] += $transaction->amount;
                
                // Group by potential manifest
                if (!isset($analysis['by_potential_manifest'][$manifestId])) {
                    $manifest = Manifest::find($manifestId);
                    $analysis['by_potential_manifest'][$manifestId] = [
                        'name' => $manifest ? $manifest->name : "Manifest #{$manifestId}",
                        'count' => 0,
                        'amount' => 0,
                    ];
                }
                $analysis['by_potential_manifest'][$manifestId]['count']++;
                $analysis['by_potential_manifest'][$manifestId]['amount'] += $transaction->amount;
                
                // Store examples
                if (count($analysis['examples']) < 5) {
                    $analysis['examples'][] = [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'customer' => $transaction->user->full_name ?? 'Unknown',
                        'current_reference' => $transaction->reference_type,
                        'potential_manifest' => $manifest->name ?? "Manifest #{$manifestId}",
                    ];
                }
            }
        }

        return $analysis;
    }

    /**
     * Display linkable transaction analysis
     */
    private function displayLinkableAnalysis(array $analysis): void
    {
        $this->info('LINKABLE TRANSACTIONS ANALYSIS:');
        $this->line("Total transactions that could be linked to manifests: {$analysis['total_linkable']}");
        $this->line("Total amount: $" . number_format($analysis['total_amount'], 2));
        $this->line('');

        if ($analysis['total_linkable'] > 0) {
            $this->line('By Transaction Type:');
            foreach ($analysis['by_type'] as $type => $count) {
                $this->line("  - " . ucfirst(str_replace('_', ' ', $type)) . ": {$count}");
            }
            $this->line('');

            $this->line('Top Customers (by transaction count):');
            $topCustomers = collect($analysis['by_customer'])
                ->sortByDesc('count')
                ->take(5);
            
            foreach ($topCustomers as $customerId => $data) {
                $this->line("  - {$data['name']}: {$data['count']} transactions, $" . number_format($data['amount'], 2));
            }
            $this->line('');

            $this->line('Top Manifests (by transaction count):');
            $topManifests = collect($analysis['by_potential_manifest'])
                ->sortByDesc('count')
                ->take(5);
            
            foreach ($topManifests as $manifestId => $data) {
                $this->line("  - {$data['name']}: {$data['count']} transactions, $" . number_format($data['amount'], 2));
            }
            $this->line('');

            if (!empty($analysis['examples'])) {
                $this->line('Example Transactions:');
                foreach ($analysis['examples'] as $example) {
                    $this->line("  - #{$example['id']}: {$example['type']} \${$example['amount']} for {$example['customer']} → {$example['potential_manifest']}");
                }
            }
        }
        $this->line('');
    }

    /**
     * Analyze manifest coverage
     */
    private function analyzeManifestCoverage(): array
    {
        $totalManifests = Manifest::count();
        $manifestsWithTransactions = Manifest::whereHas('transactions')->count();
        $manifestsWithPackages = Manifest::whereHas('packages')->count();
        
        $coverage = [];
        
        Manifest::withCount(['transactions', 'packages'])
            ->orderBy('transactions_count', 'desc')
            ->get()
            ->each(function ($manifest) use (&$coverage) {
                $coverage[] = [
                    'id' => $manifest->id,
                    'name' => $manifest->name,
                    'packages_count' => $manifest->packages_count,
                    'transactions_count' => $manifest->transactions_count,
                    'has_transactions' => $manifest->transactions_count > 0,
                    'has_packages_no_transactions' => $manifest->packages_count > 0 && $manifest->transactions_count === 0,
                ];
            });

        return [
            'total_manifests' => $totalManifests,
            'manifests_with_transactions' => $manifestsWithTransactions,
            'manifests_with_packages' => $manifestsWithPackages,
            'coverage_percentage' => $totalManifests > 0 ? ($manifestsWithTransactions / $totalManifests) * 100 : 0,
            'manifests' => $coverage,
        ];
    }

    /**
     * Display manifest coverage analysis
     */
    private function displayManifestCoverage(array $coverage): void
    {
        $this->info('MANIFEST COVERAGE ANALYSIS:');
        $this->line("Total manifests: {$coverage['total_manifests']}");
        $this->line("Manifests with linked transactions: {$coverage['manifests_with_transactions']}");
        $this->line("Manifests with packages: {$coverage['manifests_with_packages']}");
        $this->line("Coverage percentage: " . number_format($coverage['coverage_percentage'], 1) . "%");
        $this->line('');

        $manifestsWithPackagesNoTransactions = collect($coverage['manifests'])
            ->where('has_packages_no_transactions', true)
            ->count();

        if ($manifestsWithPackagesNoTransactions > 0) {
            $this->warn("Found {$manifestsWithPackagesNoTransactions} manifests with packages but no linked transactions!");
            $this->line('These manifests might have transactions that could be backfilled:');
            
            collect($coverage['manifests'])
                ->where('has_packages_no_transactions', true)
                ->take(10)
                ->each(function ($manifest) {
                    $this->line("  - {$manifest['name']} ({$manifest['packages_count']} packages)");
                });
        }
        $this->line('');
    }

    /**
     * Display detailed breakdown
     */
    private function displayDetailedBreakdown(): void
    {
        $this->info('DETAILED BREAKDOWN:');
        
        // Show transaction types and their reference patterns
        $typeAnalysis = CustomerTransaction::select('type', 'reference_type', DB::raw('COUNT(*) as count'))
            ->groupBy('type', 'reference_type')
            ->orderBy('type')
            ->orderBy('count', 'desc')
            ->get();

        $this->line('Transaction Types and Reference Patterns:');
        $currentType = null;
        foreach ($typeAnalysis as $row) {
            if ($currentType !== $row->type) {
                $currentType = $row->type;
                $this->line("  " . ucfirst(str_replace('_', ' ', $row->type)) . ":");
            }
            $refType = $row->reference_type ?: 'null';
            $this->line("    - {$refType}: {$row->count}");
        }
        $this->line('');
    }

    /**
     * Export results to CSV
     */
    private function exportToCSV(string $filename, array $stats, array $linkable, array $coverage): void
    {
        $this->info("Exporting results to {$filename}...");
        
        $csv = fopen($filename, 'w');
        
        // Write headers and data
        fputcsv($csv, ['Analysis Type', 'Metric', 'Value']);
        
        // Overall stats
        fputcsv($csv, ['Overall', 'Total Transactions', $stats['total_transactions']]);
        fputcsv($csv, ['Overall', 'Linked to Manifests', $stats['linked_to_manifests']]);
        fputcsv($csv, ['Overall', 'Manifest Link Percentage', number_format($stats['manifest_link_percentage'], 2) . '%']);
        
        // Linkable analysis
        fputcsv($csv, ['Linkable', 'Total Linkable', $linkable['total_linkable']]);
        fputcsv($csv, ['Linkable', 'Total Amount', '$' . number_format($linkable['total_amount'], 2)]);
        
        // Coverage
        fputcsv($csv, ['Coverage', 'Total Manifests', $coverage['total_manifests']]);
        fputcsv($csv, ['Coverage', 'Manifests with Transactions', $coverage['manifests_with_transactions']]);
        fputcsv($csv, ['Coverage', 'Coverage Percentage', number_format($coverage['coverage_percentage'], 2) . '%']);
        
        fclose($csv);
        $this->info("Results exported to {$filename}");
    }

    /**
     * Display recommendations
     */
    private function displayRecommendations(array $linkable): void
    {
        $this->info('RECOMMENDATIONS:');
        
        if ($linkable['total_linkable'] > 0) {
            $this->line("✓ Run backfill command to link {$linkable['total_linkable']} transactions to manifests");
            $this->line("  Command: php artisan backfill:transaction-manifest-links --dry-run");
            $this->line('');
        }
        
        $this->line('✓ Use the transaction management interface to create new transactions with manifest links');
        $this->line('✓ Monitor the manifest coverage to ensure all manifests have proper transaction linking');
        $this->line('✓ Consider setting up automated processes to link transactions during package operations');
    }

    /**
     * Find potential manifest for a transaction (same logic as backfill command)
     */
    private function findPotentialManifest(CustomerTransaction $transaction): ?int
    {
        // Direct package reference
        if ($transaction->reference_type === 'App\\Models\\Package' && $transaction->reference_id) {
            $package = Package::find($transaction->reference_id);
            if ($package && $package->manifest_id) {
                return $package->manifest_id;
            }
        }

        // Package distribution reference
        if (in_array($transaction->reference_type, ['App\\Models\\PackageDistribution', 'package_distribution', 'consolidated_package_distribution']) && $transaction->reference_id) {
            $distribution = PackageDistribution::find($transaction->reference_id);
            if ($distribution) {
                $manifestIds = $distribution->packages()->pluck('manifest_id')->filter()->unique();
                if ($manifestIds->count() === 1) {
                    return $manifestIds->first();
                }
            }
        }

        // Metadata package IDs
        if ($transaction->metadata && isset($transaction->metadata['package_ids'])) {
            $packageIds = $transaction->metadata['package_ids'];
            if (is_array($packageIds) && !empty($packageIds)) {
                $manifestIds = Package::whereIn('id', $packageIds)
                    ->pluck('manifest_id')
                    ->filter()
                    ->unique();
                
                if ($manifestIds->count() === 1) {
                    return $manifestIds->first();
                }
            }
        }

        return null;
    }
}
<?php

namespace Database\Seeders;

use App\Enums\PackageStatus;
use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageStatusNormalizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting package status normalization...');
        
        // Identify all unique legacy status values
        $legacyStatuses = $this->identifyLegacyStatuses();
        $this->command->info('Found ' . count($legacyStatuses) . ' unique status values');
        
        // Map legacy statuses to normalized values
        $statusMappings = $this->mapLegacyStatuses($legacyStatuses);
        
        // Display mapping plan
        $this->displayMappingPlan($statusMappings);
        
        // Update package statuses
        $results = $this->updatePackageStatuses($statusMappings);
        
        // Generate and display report
        $this->generateReport($results, $statusMappings);
        
        $this->command->info('Package status normalization completed!');
    }

    /**
     * Identify all unique status values currently in the packages table
     */
    private function identifyLegacyStatuses(): array
    {
        return DB::table('packages')
            ->select('status')
            ->distinct()
            ->whereNotNull('status')
            ->pluck('status')
            ->toArray();
    }

    /**
     * Map legacy status values to normalized PackageStatus enum values
     */
    private function mapLegacyStatuses(array $legacyStatuses): array
    {
        $mappings = [];
        $unmappable = [];

        foreach ($legacyStatuses as $legacyStatus) {
            try {
                $normalizedStatus = PackageStatus::fromLegacyStatus($legacyStatus);
                $mappings[$legacyStatus] = $normalizedStatus->value;
            } catch (\Exception $e) {
                $unmappable[] = $legacyStatus;
                // Default to pending for unmappable statuses
                $mappings[$legacyStatus] = PackageStatus::PENDING;
                
                Log::warning('Unmappable status found during normalization', [
                    'legacy_status' => $legacyStatus,
                    'defaulted_to' => PackageStatus::PENDING,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($unmappable)) {
            $this->command->warn('Found unmappable statuses: ' . implode(', ', $unmappable));
            $this->command->warn('These will be set to "pending" status and logged for manual review.');
        }

        return $mappings;
    }

    /**
     * Display the mapping plan to the user
     */
    private function displayMappingPlan(array $statusMappings): void
    {
        $this->command->info("\nStatus Mapping Plan:");
        $this->command->table(
            ['Legacy Status', 'Normalized Status', 'Count'],
            collect($statusMappings)->map(function ($normalizedStatus, $legacyStatus) {
                $count = DB::table('packages')->where('status', $legacyStatus)->count();
                return [$legacyStatus, $normalizedStatus, $count];
            })->toArray()
        );
    }

    /**
     * Update all package records with normalized status values
     */
    private function updatePackageStatuses(array $statusMappings): array
    {
        $results = [
            'total_updated' => 0,
            'mappings' => [],
        ];

        DB::beginTransaction();
        
        try {
            foreach ($statusMappings as $legacyStatus => $normalizedStatus) {
                $count = DB::table('packages')
                    ->where('status', $legacyStatus)
                    ->update(['status' => $normalizedStatus]);
                
                $results['mappings'][$legacyStatus] = [
                    'normalized_to' => $normalizedStatus,
                    'count' => $count,
                ];
                
                $results['total_updated'] += $count;
                
                $this->command->info("Updated {$count} packages from '{$legacyStatus}' to '{$normalizedStatus}'");
            }
            
            DB::commit();
            
            Log::info('Package status normalization completed successfully', [
                'total_updated' => $results['total_updated'],
                'mappings' => $results['mappings'],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Package status normalization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->command->error('Failed to update package statuses: ' . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Generate summary report of the normalization process
     */
    private function generateReport(array $results, array $statusMappings): void
    {
        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info('PACKAGE STATUS NORMALIZATION REPORT');
        $this->command->info(str_repeat('=', 60));
        
        $this->command->info("Total packages updated: {$results['total_updated']}");
        $this->command->info("Unique status mappings: " . count($statusMappings));
        
        $this->command->info("\nDetailed Mapping Results:");
        foreach ($results['mappings'] as $legacyStatus => $mapping) {
            $this->command->info("  {$legacyStatus} → {$mapping['normalized_to']}: {$mapping['count']} packages");
        }
        
        // Verify normalization
        $this->verifyNormalization();
        
        $this->command->info("\nNormalization completed at: " . now()->format('Y-m-d H:i:s'));
        $this->command->info(str_repeat('=', 60));
    }

    /**
     * Verify that all packages now have normalized status values
     */
    private function verifyNormalization(): void
    {
        $validStatuses = PackageStatus::values();
        $invalidStatuses = DB::table('packages')
            ->select('status')
            ->distinct()
            ->whereNotIn('status', $validStatuses)
            ->pluck('status')
            ->toArray();

        if (empty($invalidStatuses)) {
            $this->command->info("\n✅ Verification passed: All packages have normalized status values");
        } else {
            $this->command->error("\n❌ Verification failed: Found invalid statuses: " . implode(', ', $invalidStatuses));
            
            Log::error('Status normalization verification failed', [
                'invalid_statuses' => $invalidStatuses,
            ]);
        }

        // Show current status distribution
        $this->showStatusDistribution();
    }

    /**
     * Show the current distribution of status values
     */
    private function showStatusDistribution(): void
    {
        $distribution = DB::table('packages')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get();

        $this->command->info("\nCurrent Status Distribution:");
        $tableData = $distribution->map(function ($item) {
            $statusEnum = PackageStatus::from($item->status);
            return [
                $item->status,
                $statusEnum->getLabel(),
                $item->count,
                $statusEnum->getBadgeClass(),
            ];
        })->toArray();

        $this->command->table(
            ['Status Value', 'Label', 'Count', 'Badge Class'],
            $tableData
        );
    }

    /**
     * Get packages that need manual review (if any)
     */
    public function getPackagesNeedingReview(): array
    {
        // This could be extended to identify packages that might need special attention
        // For now, we'll return packages that were defaulted to pending from unmappable statuses
        return [];
    }
}

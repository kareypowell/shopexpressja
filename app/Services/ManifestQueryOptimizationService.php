<?php

namespace App\Services;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManifestQueryOptimizationService
{
    /**
     * Get optimized package data for weight calculations
     *
     * @param Manifest $manifest
     * @return Collection
     */
    public function getPackagesForWeightCalculation(Manifest $manifest)
    {
        try {
            return $manifest->packages()
                ->select(['id', 'tracking_number', 'weight', 'freight_price', 'clearance_fee', 'storage_fee', 'delivery_fee'])
                ->whereNotNull('weight')
                ->where('weight', '>', 0)
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get packages for weight calculation', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return collect();
        }
    }

    /**
     * Get optimized package data for volume calculations
     *
     * @param Manifest $manifest
     * @return Collection
     */
    public function getPackagesForVolumeCalculation(Manifest $manifest)
    {
        try {
            return $manifest->packages()
                ->select([
                    'id', 
                    'tracking_number', 
                    'cubic_feet', 
                    'length_inches', 
                    'width_inches', 
                    'height_inches',
                    'freight_price',
                    'clearance_fee',
                    'storage_fee',
                    'delivery_fee'
                ])
                ->where(function ($query) {
                    $query->where(function ($subQuery) {
                        // Has cubic_feet data
                        $subQuery->whereNotNull('cubic_feet')
                                ->where('cubic_feet', '>', 0);
                    })->orWhere(function ($subQuery) {
                        // Has dimension data for calculation
                        $subQuery->whereNotNull('length_inches')
                                ->whereNotNull('width_inches')
                                ->whereNotNull('height_inches')
                                ->where('length_inches', '>', 0)
                                ->where('width_inches', '>', 0)
                                ->where('height_inches', '>', 0);
                    });
                })
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get packages for volume calculation', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return collect();
        }
    }

    /**
     * Get optimized summary statistics using raw SQL for better performance
     *
     * @param Manifest $manifest
     * @return array
     */
    public function getOptimizedSummaryStats(Manifest $manifest)
    {
        try {
            $stats = DB::table('packages')
                ->where('manifest_id', $manifest->id)
                ->selectRaw('
                    COUNT(*) as total_packages,
                    SUM(CASE WHEN weight IS NOT NULL AND weight > 0 THEN weight ELSE 0 END) as total_weight,
                    COUNT(CASE WHEN weight IS NOT NULL AND weight > 0 THEN 1 END) as packages_with_weight,
                    SUM(CASE WHEN cubic_feet IS NOT NULL AND cubic_feet > 0 THEN cubic_feet ELSE 0 END) as total_volume_direct,
                    COUNT(CASE WHEN cubic_feet IS NOT NULL AND cubic_feet > 0 THEN 1 END) as packages_with_volume_direct,
                    COUNT(CASE WHEN length_inches IS NOT NULL AND width_inches IS NOT NULL AND height_inches IS NOT NULL 
                              AND length_inches > 0 AND width_inches > 0 AND height_inches > 0 THEN 1 END) as packages_with_dimensions,
                    SUM(CASE WHEN freight_price IS NOT NULL AND freight_price >= 0 THEN freight_price ELSE 0 END + 
                        CASE WHEN clearance_fee IS NOT NULL AND clearance_fee >= 0 THEN clearance_fee ELSE 0 END +
                        CASE WHEN storage_fee IS NOT NULL AND storage_fee >= 0 THEN storage_fee ELSE 0 END +
                        CASE WHEN delivery_fee IS NOT NULL AND delivery_fee >= 0 THEN delivery_fee ELSE 0 END) as total_value,
                    COUNT(CASE WHEN consolidated_package_id IS NULL THEN 1 END) as individual_packages_count,
                    COUNT(DISTINCT CASE WHEN consolidated_package_id IS NOT NULL THEN consolidated_package_id END) as consolidated_packages_count
                ')
                ->first();

            return [
                'total_packages' => (int) $stats->total_packages,
                'total_weight' => (float) $stats->total_weight,
                'packages_with_weight' => (int) $stats->packages_with_weight,
                'total_volume_direct' => (float) $stats->total_volume_direct,
                'packages_with_volume_direct' => (int) $stats->packages_with_volume_direct,
                'packages_with_dimensions' => (int) $stats->packages_with_dimensions,
                'total_value' => (float) $stats->total_value,
                'individual_packages_count' => (int) $stats->individual_packages_count,
                'consolidated_packages_count' => (int) $stats->consolidated_packages_count,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get optimized summary stats', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_packages' => 0,
                'total_weight' => 0.0,
                'packages_with_weight' => 0,
                'total_volume_direct' => 0.0,
                'packages_with_volume_direct' => 0,
                'packages_with_dimensions' => 0,
                'total_value' => 0.0,
                'individual_packages_count' => 0,
                'consolidated_packages_count' => 0,
            ];
        }
    }

    /**
     * Get packages with missing weight data for validation
     *
     * @param Manifest $manifest
     * @return Collection
     */
    public function getPackagesMissingWeight(Manifest $manifest)
    {
        try {
            return $manifest->packages()
                ->select(['id', 'tracking_number'])
                ->where(function ($query) {
                    $query->whereNull('weight')
                          ->orWhere('weight', '<=', 0);
                })
                ->limit(100) // Limit for performance
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get packages missing weight', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return collect();
        }
    }

    /**
     * Get packages with missing volume data for validation
     *
     * @param Manifest $manifest
     * @return Collection
     */
    public function getPackagesMissingVolume(Manifest $manifest)
    {
        try {
            return $manifest->packages()
                ->select(['id', 'tracking_number'])
                ->where(function ($query) {
                    $query->where(function ($subQuery) {
                        // Missing cubic_feet
                        $subQuery->whereNull('cubic_feet')
                                ->orWhere('cubic_feet', '<=', 0);
                    })->where(function ($subQuery) {
                        // AND missing dimensions
                        $subQuery->whereNull('length_inches')
                                ->orWhereNull('width_inches')
                                ->orWhereNull('height_inches')
                                ->orWhere('length_inches', '<=', 0)
                                ->orWhere('width_inches', '<=', 0)
                                ->orWhere('height_inches', '<=', 0);
                    });
                })
                ->limit(100) // Limit for performance
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get packages missing volume', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return collect();
        }
    }

    /**
     * Get optimized individual packages count
     *
     * @param Manifest $manifest
     * @return int
     */
    public function getIndividualPackagesCount(Manifest $manifest)
    {
        try {
            return $manifest->packages()
                ->whereNull('consolidated_package_id')
                ->count();
        } catch (\Exception $e) {
            Log::error('Failed to get individual packages count', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Get optimized consolidated packages count
     *
     * @param Manifest $manifest
     * @return int
     */
    public function getConsolidatedPackagesCount(Manifest $manifest)
    {
        try {
            return $manifest->packages()
                ->whereNotNull('consolidated_package_id')
                ->distinct('consolidated_package_id')
                ->count('consolidated_package_id');
        } catch (\Exception $e) {
            Log::error('Failed to get consolidated packages count', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Preload related data for manifest to reduce N+1 queries
     *
     * @param Manifest $manifest
     * @return Manifest
     */
    public function preloadManifestData(Manifest $manifest)
    {
        try {
            return $manifest->load([
                'packages:id,manifest_id,tracking_number,weight,cubic_feet,length_inches,width_inches,height_inches,freight_price,clearance_fee,storage_fee,delivery_fee,consolidated_package_id',
                'packages.consolidatedPackage:id,customer_id,status'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to preload manifest data', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
            
            return $manifest;
        }
    }

    /**
     * Get query execution statistics for monitoring
     *
     * @return array
     */
    public function getQueryStatistics()
    {
        try {
            // Enable query logging temporarily
            DB::enableQueryLog();
            
            // This would be called after operations to get stats
            $queries = DB::getQueryLog();
            
            return [
                'total_queries' => count($queries),
                'queries' => $queries,
                'slow_queries' => array_filter($queries, function ($query) {
                    return $query['time'] > 100; // Queries taking more than 100ms
                })
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get query statistics', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_queries' => 0,
                'queries' => [],
                'slow_queries' => []
            ];
        }
    }
}
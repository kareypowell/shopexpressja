<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Package;
use App\Services\ManifestQueryOptimizationService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WeightCalculationService
{
    /**
     * Calculate total weight from a collection of packages
     *
     * @param Collection $packages
     * @return float Total weight in pounds
     * @throws InvalidArgumentException
     */
    public function calculateTotalWeight(Collection $packages): float
    {
        try {
            $this->validatePackagesCollection($packages);
            
            $totalWeight = $packages->sum(function (Package $package) {
                $weight = $package->weight ?? 0;
                
                // Validate individual weight values
                if (!is_numeric($weight) || $weight < 0) {
                    Log::warning('Invalid weight value found in package', [
                        'package_id' => $package->id ?? null,
                        'tracking_number' => $package->tracking_number ?? null,
                        'weight' => $weight
                    ]);
                    return 0;
                }
                
                // Cap extremely high weights (likely data errors)
                if ($weight > 10000) {
                    Log::warning('Extremely high weight value found, capping at 10000 lbs', [
                        'package_id' => $package->id ?? null,
                        'tracking_number' => $package->tracking_number ?? null,
                        'original_weight' => $weight
                    ]);
                    return 10000;
                }
                
                return $weight;
            });
            
            return round($totalWeight, 2);
        } catch (\Exception $e) {
            Log::error('Failed to calculate total weight', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage()
            ]);
            
            return 0.0;
        }
    }

    /**
     * Convert pounds to kilograms
     *
     * @param float $lbs Weight in pounds
     * @return float Weight in kilograms
     * @throws InvalidArgumentException
     */
    public function convertLbsToKg(float $lbs): float
    {
        if (!is_numeric($lbs) || $lbs < 0) {
            throw new InvalidArgumentException('Weight in pounds must be a non-negative number');
        }
        
        if ($lbs > 50000) {
            Log::warning('Extremely high weight conversion requested', [
                'weight_lbs' => $lbs
            ]);
        }
        
        return round($lbs * 0.453592, 2);
    }

    /**
     * Format weight units for display
     *
     * @param float $lbs Weight in pounds
     * @param float|null $kg Weight in kilograms (will be calculated if not provided)
     * @return array Formatted weight display data
     */
    public function formatWeightUnits(float $lbs, ?float $kg = null): array
    {
        try {
            // Validate input
            if (!is_numeric($lbs) || $lbs < 0) {
                Log::warning('Invalid weight provided for formatting', ['lbs' => $lbs]);
                $lbs = 0;
            }
            
            if ($kg === null) {
                $kg = $this->convertLbsToKg($lbs);
            } elseif (!is_numeric($kg) || $kg < 0) {
                Log::warning('Invalid kg weight provided for formatting', ['kg' => $kg]);
                $kg = $this->convertLbsToKg($lbs);
            }

            return [
                'lbs' => number_format($lbs, 1) . ' lbs',
                'kg' => number_format($kg, 1) . ' kg',
                'raw_lbs' => round($lbs, 2),
                'raw_kg' => round($kg, 2),
                'display' => number_format($lbs, 1) . ' lbs (' . number_format($kg, 1) . ' kg)'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to format weight units', [
                'lbs' => $lbs,
                'kg' => $kg,
                'error' => $e->getMessage()
            ]);
            
            // Return safe fallback
            return [
                'lbs' => '0.0 lbs',
                'kg' => '0.0 kg',
                'raw_lbs' => 0.0,
                'raw_kg' => 0.0,
                'display' => '0.0 lbs (0.0 kg)'
            ];
        }
    }

    /**
     * Validate weight data completeness in packages
     *
     * @param Collection $packages
     * @return array Validation results
     */
    public function validateWeightData(Collection $packages): array
    {
        $totalPackages = $packages->count();
        $packagesWithWeight = $packages->filter(function (Package $package) {
            return !is_null($package->weight) && $package->weight > 0;
        })->count();

        $missingWeightPackages = $packages->filter(function (Package $package) {
            return is_null($package->weight) || $package->weight <= 0;
        });

        return [
            'total_packages' => $totalPackages,
            'packages_with_weight' => $packagesWithWeight,
            'packages_missing_weight' => $totalPackages - $packagesWithWeight,
            'is_complete' => $packagesWithWeight === $totalPackages,
            'completion_percentage' => $totalPackages > 0 ? round(($packagesWithWeight / $totalPackages) * 100, 1) : 0,
            'missing_weight_tracking_numbers' => $missingWeightPackages->pluck('tracking_number')->toArray()
        ];
    }

    /**
     * Get weight statistics for a collection of packages
     *
     * @param Collection $packages
     * @return array Weight statistics
     */
    public function getWeightStatistics(Collection $packages): array
    {
        try {
            $this->validatePackagesCollection($packages);
            
            $packagesWithWeight = $packages->filter(function (Package $package) {
                return !is_null($package->weight) && 
                       is_numeric($package->weight) && 
                       $package->weight > 0;
            });

            if ($packagesWithWeight->isEmpty()) {
                return [
                    'total_weight_lbs' => 0,
                    'total_weight_kg' => 0,
                    'average_weight_lbs' => 0,
                    'average_weight_kg' => 0,
                    'min_weight_lbs' => 0,
                    'max_weight_lbs' => 0,
                    'formatted' => $this->formatWeightUnits(0)
                ];
            }

            $weights = $packagesWithWeight->pluck('weight')->filter(function ($weight) {
                return is_numeric($weight) && $weight > 0 && $weight <= 10000;
            });

            if ($weights->isEmpty()) {
                Log::warning('No valid weights found after filtering');
                return [
                    'total_weight_lbs' => 0,
                    'total_weight_kg' => 0,
                    'average_weight_lbs' => 0,
                    'average_weight_kg' => 0,
                    'min_weight_lbs' => 0,
                    'max_weight_lbs' => 0,
                    'formatted' => $this->formatWeightUnits(0)
                ];
            }

            $totalWeight = $weights->sum();
            $averageWeight = $weights->avg();
            $minWeight = $weights->min();
            $maxWeight = $weights->max();

            return [
                'total_weight_lbs' => round($totalWeight, 2),
                'total_weight_kg' => $this->convertLbsToKg($totalWeight),
                'average_weight_lbs' => round($averageWeight, 2),
                'average_weight_kg' => $this->convertLbsToKg($averageWeight),
                'min_weight_lbs' => $minWeight,
                'max_weight_lbs' => $maxWeight,
                'formatted' => $this->formatWeightUnits($totalWeight)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to calculate weight statistics', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_weight_lbs' => 0,
                'total_weight_kg' => 0,
                'average_weight_lbs' => 0,
                'average_weight_kg' => 0,
                'min_weight_lbs' => 0,
                'max_weight_lbs' => 0,
                'formatted' => $this->formatWeightUnits(0)
            ];
        }
    }

    /**
     * Validate packages collection
     *
     * @param Collection $packages
     * @throws InvalidArgumentException
     */
    protected function validatePackagesCollection(Collection $packages): void
    {
        if ($packages->isEmpty()) {
            return; // Empty collection is valid
        }

        // Check if all items are Package instances
        $invalidItems = $packages->filter(function ($item) {
            return !($item instanceof Package);
        });

        if ($invalidItems->isNotEmpty()) {
            throw new InvalidArgumentException('Collection must contain only Package instances');
        }
    }

    /**
     * Validate calculation results before returning
     *
     * @param array $results
     * @return array Validated results
     */
    public function validateCalculationResults(array $results): array
    {
        $validatedResults = [];
        
        foreach ($results as $key => $value) {
            if (is_numeric($value)) {
                // Ensure numeric values are reasonable
                if ($value < 0) {
                    Log::warning("Negative value found in calculation results: {$key} = {$value}");
                    $validatedResults[$key] = 0;
                } elseif ($value > 1000000) {
                    Log::warning("Extremely high value found in calculation results: {$key} = {$value}");
                    $validatedResults[$key] = 1000000; // Cap at reasonable maximum
                } else {
                    $validatedResults[$key] = $value;
                }
            } else {
                $validatedResults[$key] = $value;
            }
        }
        
        return $validatedResults;
    }
}
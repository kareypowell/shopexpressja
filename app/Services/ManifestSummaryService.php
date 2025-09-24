<?php

namespace App\Services;

use App\Models\Manifest;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ManifestSummaryService
{
    protected WeightCalculationService $weightService;
    protected VolumeCalculationService $volumeService;

    public function __construct(
        WeightCalculationService $weightService,
        VolumeCalculationService $volumeService
    ) {
        $this->weightService = $weightService;
        $this->volumeService = $volumeService;
    }

    /**
     * Get comprehensive summary for a manifest
     *
     * @param Manifest $manifest
     * @return array Complete manifest summary
     */
    public function getManifestSummary(Manifest $manifest): array
    {
        try {
            $packages = $manifest->packages;
            $manifestType = $this->getManifestType($manifest);

            $baseSummary = [
                'manifest_type' => $manifestType,
                'package_count' => max(0, $packages->count()),
                'total_value' => $this->calculateTotalValue($packages),
            ];

            if ($manifestType === 'air') {
                $airSummary = $this->calculateAirManifestSummary($packages);
                return array_merge($baseSummary, $this->validateSummaryData($airSummary));
            } elseif ($manifestType === 'sea') {
                $seaSummary = $this->calculateSeaManifestSummary($packages);
                return array_merge($baseSummary, $this->validateSummaryData($seaSummary));
            }

            // Fallback for unknown manifest types - include both weight and volume
            $airSummary = $this->calculateAirManifestSummary($packages);
            $seaSummary = $this->calculateSeaManifestSummary($packages);
            
            return array_merge(
                $baseSummary,
                $this->validateSummaryData($airSummary),
                $this->validateSummaryData($seaSummary)
            );
        } catch (\Exception $e) {
            Log::error('Failed to get manifest summary', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return safe fallback summary
            return $this->getFallbackSummary($manifest);
        }
    }

    /**
     * Calculate summary for Air manifests (weight-focused)
     *
     * @param Collection $packages
     * @return array Air manifest summary data
     */
    public function calculateAirManifestSummary(Collection $packages): array
    {
        try {
            $weightStats = $this->weightService->getWeightStatistics($packages);
            $weightValidation = $this->weightService->validateWeightData($packages);

            // Validate weight statistics
            $weightStats = $this->weightService->validateCalculationResults($weightStats);

            return [
                'weight' => [
                    'total_lbs' => $weightStats['total_weight_lbs'] ?? 0,
                    'total_kg' => $weightStats['total_weight_kg'] ?? 0,
                    'average_lbs' => $weightStats['average_weight_lbs'] ?? 0,
                    'average_kg' => $weightStats['average_weight_kg'] ?? 0,
                    'formatted' => $weightStats['formatted'] ?? $this->weightService->formatWeightUnits(0)
                ],
                'weight_validation' => $weightValidation,
                'incomplete_data' => !($weightValidation['is_complete'] ?? false),
                'primary_metric' => 'weight'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to calculate air manifest summary', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'weight' => [
                    'total_lbs' => 0,
                    'total_kg' => 0,
                    'average_lbs' => 0,
                    'average_kg' => 0,
                    'formatted' => $this->weightService->formatWeightUnits(0)
                ],
                'weight_validation' => [
                    'total_packages' => $packages->count(),
                    'packages_with_weight' => 0,
                    'packages_missing_weight' => $packages->count(),
                    'is_complete' => false,
                    'completion_percentage' => 0,
                    'missing_weight_tracking_numbers' => []
                ],
                'incomplete_data' => true,
                'primary_metric' => 'weight'
            ];
        }
    }

    /**
     * Calculate summary for Sea manifests (volume-focused)
     *
     * @param Collection $packages
     * @return array Sea manifest summary data
     */
    public function calculateSeaManifestSummary(Collection $packages): array
    {
        try {
            $volumeStats = $this->volumeService->getVolumeStatistics($packages);
            $volumeValidation = $this->volumeService->validateVolumeData($packages);

            // Validate volume statistics
            $volumeStats = $this->volumeService->validateCalculationResults($volumeStats);

            return [
                'volume' => [
                    'total_cubic_feet' => $volumeStats['total_volume'] ?? 0,
                    'average_cubic_feet' => $volumeStats['average_volume'] ?? 0,
                    'formatted' => $volumeStats['formatted'] ?? $this->volumeService->getVolumeDisplayData(0)
                ],
                'volume_validation' => $volumeValidation,
                'incomplete_data' => !($volumeValidation['is_complete'] ?? false),
                'primary_metric' => 'volume'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to calculate sea manifest summary', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'volume' => [
                    'total_cubic_feet' => 0,
                    'average_cubic_feet' => 0,
                    'formatted' => $this->volumeService->getVolumeDisplayData(0)
                ],
                'volume_validation' => [
                    'total_packages' => $packages->count(),
                    'packages_with_volume' => 0,
                    'packages_missing_volume' => $packages->count(),
                    'is_complete' => false,
                    'completion_percentage' => 0,
                    'missing_volume_tracking_numbers' => []
                ],
                'incomplete_data' => true,
                'primary_metric' => 'volume'
            ];
        }
    }

    /**
     * Determine manifest type
     *
     * @param Manifest $manifest
     * @return string Manifest type ('air', 'sea', or 'unknown')
     */
    public function getManifestType(Manifest $manifest): string
    {
        if (!is_null($manifest->type)) {
            return strtolower($manifest->type);
        }

        // Fallback logic based on manifest fields
        if (!is_null($manifest->vessel_name) || !is_null($manifest->voyage_number)) {
            return 'sea';
        }

        if (!is_null($manifest->flight_number) || !is_null($manifest->flight_destination)) {
            return 'air';
        }

        return 'unknown';
    }

    /**
     * Calculate total cost of packages (freight + clearance + storage + delivery)
     *
     * @param Collection $packages
     * @return float Total cost
     */
    protected function calculateTotalValue(Collection $packages): float
    {
        try {
            $totalValue = $packages->sum(function (Package $package) {
                // Calculate total cost from individual cost components
                $cost = ($package->freight_price ?? 0) + 
                       ($package->clearance_fee ?? 0) + 
                       ($package->storage_fee ?? 0) + 
                       ($package->delivery_fee ?? 0);
                
                // Validate cost value
                if (!is_numeric($cost) || $cost < 0) {
                    Log::warning('Invalid cost value found in package', [
                        'package_id' => $package->id ?? null,
                        'tracking_number' => $package->tracking_number ?? null,
                        'cost' => $cost
                    ]);
                    return 0;
                }
                
                // Cap extremely high costs (likely data errors)
                if ($cost > 100000) {
                    Log::warning('Extremely high cost value found, capping at $100,000', [
                        'package_id' => $package->id ?? null,
                        'tracking_number' => $package->tracking_number ?? null,
                        'original_cost' => $cost
                    ]);
                    return 100000;
                }
                
                return $cost;
            });
            
            return round($totalValue, 2);
        } catch (\Exception $e) {
            Log::error('Failed to calculate total value', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage()
            ]);
            
            return 0.0;
        }
    }

    /**
     * Validate summary data before returning
     *
     * @param array $summaryData
     * @return array Validated summary data
     */
    protected function validateSummaryData(array $summaryData): array
    {
        $validatedData = [];
        
        foreach ($summaryData as $key => $value) {
            if (is_array($value)) {
                $validatedData[$key] = $this->validateSummaryData($value);
            } elseif (is_numeric($value)) {
                // Ensure numeric values are reasonable
                if ($value < 0) {
                    Log::warning("Negative value found in summary data: {$key} = {$value}");
                    $validatedData[$key] = 0;
                } else {
                    $validatedData[$key] = $value;
                }
            } else {
                $validatedData[$key] = $value;
            }
        }
        
        return $validatedData;
    }

    /**
     * Get fallback summary when calculation fails
     *
     * @param Manifest $manifest
     * @return array Safe fallback summary
     */
    protected function getFallbackSummary(Manifest $manifest): array
    {
        $manifestType = 'unknown';
        
        try {
            $manifestType = $this->getManifestType($manifest);
        } catch (\Exception $e) {
            Log::error('Failed to determine manifest type for fallback', [
                'manifest_id' => $manifest->id,
                'error' => $e->getMessage()
            ]);
        }
        
        $fallbackSummary = [
            'manifest_type' => $manifestType,
            'package_count' => 0,
            'total_value' => 0.0,
            'incomplete_data' => true,
            'primary_metric' => $manifestType === 'air' ? 'weight' : 'volume'
        ];
        
        if ($manifestType === 'air') {
            $fallbackSummary['weight'] = [
                'total_lbs' => 0,
                'total_kg' => 0,
                'average_lbs' => 0,
                'average_kg' => 0,
                'formatted' => $this->weightService->formatWeightUnits(0)
            ];
            $fallbackSummary['weight_validation'] = [
                'total_packages' => 0,
                'packages_with_weight' => 0,
                'packages_missing_weight' => 0,
                'is_complete' => false,
                'completion_percentage' => 0,
                'missing_weight_tracking_numbers' => []
            ];
        } elseif ($manifestType === 'sea') {
            $fallbackSummary['volume'] = [
                'total_cubic_feet' => 0,
                'average_cubic_feet' => 0,
                'formatted' => $this->volumeService->getVolumeDisplayData(0)
            ];
            $fallbackSummary['volume_validation'] = [
                'total_packages' => 0,
                'packages_with_volume' => 0,
                'packages_missing_volume' => 0,
                'is_complete' => false,
                'completion_percentage' => 0,
                'missing_volume_tracking_numbers' => []
            ];
        }
        
        return $fallbackSummary;
    }

    /**
     * Get summary for display in UI components
     *
     * @param Manifest $manifest
     * @return array UI-ready summary data
     */
    public function getDisplaySummary(Manifest $manifest): array
    {
        $summary = $this->getManifestSummary($manifest);
        $manifestType = $summary['manifest_type'];

        $displayData = [
            'manifest_type' => $manifestType,
            'package_count' => $summary['package_count'],
            'total_value' => number_format($summary['total_value'], 2),
            'incomplete_data' => $summary['incomplete_data'] ?? false,
        ];

        if ($manifestType === 'air' && isset($summary['weight'])) {
            $displayData['primary_metric'] = [
                'type' => 'weight',
                'label' => 'Total Weight',
                'value' => $summary['weight']['formatted']['lbs'],
                'secondary' => $summary['weight']['formatted']['kg'],
                'display' => $summary['weight']['formatted']['display']
            ];
        } elseif ($manifestType === 'sea' && isset($summary['volume'])) {
            $displayData['primary_metric'] = [
                'type' => 'volume',
                'label' => 'Total Volume',
                'value' => $summary['volume']['formatted']['display'],
                'secondary' => null,
                'display' => $summary['volume']['formatted']['display']
            ];
        }

        return $displayData;
    }

    /**
     * Get validation warnings for incomplete data
     *
     * @param Manifest $manifest
     * @return array Validation warnings
     */
    public function getValidationWarnings(Manifest $manifest): array
    {
        $summary = $this->getManifestSummary($manifest);
        $warnings = [];

        if ($summary['manifest_type'] === 'air' && isset($summary['weight_validation'])) {
            $validation = $summary['weight_validation'];
            if (!$validation['is_complete']) {
                $warnings[] = [
                    'type' => 'weight',
                    'message' => "Weight data missing for {$validation['packages_missing_weight']} out of {$validation['total_packages']} packages",
                    'completion_percentage' => $validation['completion_percentage'],
                    'missing_packages' => $validation['missing_weight_tracking_numbers']
                ];
            }
        }

        if ($summary['manifest_type'] === 'sea' && isset($summary['volume_validation'])) {
            $validation = $summary['volume_validation'];
            if (!$validation['is_complete']) {
                $warnings[] = [
                    'type' => 'volume',
                    'message' => "Volume data missing for {$validation['packages_missing_volume']} out of {$validation['total_packages']} packages",
                    'completion_percentage' => $validation['completion_percentage'],
                    'missing_packages' => $validation['missing_volume_tracking_numbers']
                ];
            }
        }

        return $warnings;
    }

    /**
     * Check if manifest has complete data for its type
     *
     * @param Manifest $manifest
     * @return bool
     */
    public function hasCompleteData(Manifest $manifest): bool
    {
        $summary = $this->getManifestSummary($manifest);
        return !($summary['incomplete_data'] ?? true);
    }
}
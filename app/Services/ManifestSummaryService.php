<?php

namespace App\Services;

use App\Models\Manifest;
use App\Models\Package;
use Illuminate\Support\Collection;

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
        $packages = $manifest->packages;
        $manifestType = $this->getManifestType($manifest);

        $baseSummary = [
            'manifest_type' => $manifestType,
            'package_count' => $packages->count(),
            'total_value' => $this->calculateTotalValue($packages),
        ];

        if ($manifestType === 'air') {
            return array_merge($baseSummary, $this->calculateAirManifestSummary($packages));
        } elseif ($manifestType === 'sea') {
            return array_merge($baseSummary, $this->calculateSeaManifestSummary($packages));
        }

        // Fallback for unknown manifest types - include both weight and volume
        return array_merge(
            $baseSummary,
            $this->calculateAirManifestSummary($packages),
            $this->calculateSeaManifestSummary($packages)
        );
    }

    /**
     * Calculate summary for Air manifests (weight-focused)
     *
     * @param Collection $packages
     * @return array Air manifest summary data
     */
    public function calculateAirManifestSummary(Collection $packages): array
    {
        $weightStats = $this->weightService->getWeightStatistics($packages);
        $weightValidation = $this->weightService->validateWeightData($packages);

        return [
            'weight' => [
                'total_lbs' => $weightStats['total_weight_lbs'],
                'total_kg' => $weightStats['total_weight_kg'],
                'average_lbs' => $weightStats['average_weight_lbs'],
                'average_kg' => $weightStats['average_weight_kg'],
                'formatted' => $weightStats['formatted']
            ],
            'weight_validation' => $weightValidation,
            'incomplete_data' => !$weightValidation['is_complete'],
            'primary_metric' => 'weight'
        ];
    }

    /**
     * Calculate summary for Sea manifests (volume-focused)
     *
     * @param Collection $packages
     * @return array Sea manifest summary data
     */
    public function calculateSeaManifestSummary(Collection $packages): array
    {
        $volumeStats = $this->volumeService->getVolumeStatistics($packages);
        $volumeValidation = $this->volumeService->validateVolumeData($packages);

        return [
            'volume' => [
                'total_cubic_feet' => $volumeStats['total_volume'],
                'average_cubic_feet' => $volumeStats['average_volume'],
                'formatted' => $volumeStats['formatted']
            ],
            'volume_validation' => $volumeValidation,
            'incomplete_data' => !$volumeValidation['is_complete'],
            'primary_metric' => 'volume'
        ];
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
     * Calculate total estimated value of packages
     *
     * @param Collection $packages
     * @return float Total estimated value
     */
    protected function calculateTotalValue(Collection $packages): float
    {
        return $packages->sum(function (Package $package) {
            return $package->estimated_value ?? 0;
        });
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
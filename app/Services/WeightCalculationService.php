<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Package;

class WeightCalculationService
{
    /**
     * Calculate total weight from a collection of packages
     *
     * @param Collection $packages
     * @return float Total weight in pounds
     */
    public function calculateTotalWeight(Collection $packages): float
    {
        return $packages->sum(function (Package $package) {
            return $package->weight ?? 0;
        });
    }

    /**
     * Convert pounds to kilograms
     *
     * @param float $lbs Weight in pounds
     * @return float Weight in kilograms
     */
    public function convertLbsToKg(float $lbs): float
    {
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
        if ($kg === null) {
            $kg = $this->convertLbsToKg($lbs);
        }

        return [
            'lbs' => number_format($lbs, 1) . ' lbs',
            'kg' => number_format($kg, 1) . ' kg',
            'raw_lbs' => $lbs,
            'raw_kg' => $kg,
            'display' => number_format($lbs, 1) . ' lbs (' . number_format($kg, 1) . ' kg)'
        ];
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
        $packagesWithWeight = $packages->filter(function (Package $package) {
            return !is_null($package->weight) && $package->weight > 0;
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

        $weights = $packagesWithWeight->pluck('weight');
        $totalWeight = $weights->sum();
        $averageWeight = $weights->avg();
        $minWeight = $weights->min();
        $maxWeight = $weights->max();

        return [
            'total_weight_lbs' => $totalWeight,
            'total_weight_kg' => $this->convertLbsToKg($totalWeight),
            'average_weight_lbs' => round($averageWeight, 2),
            'average_weight_kg' => $this->convertLbsToKg($averageWeight),
            'min_weight_lbs' => $minWeight,
            'max_weight_lbs' => $maxWeight,
            'formatted' => $this->formatWeightUnits($totalWeight)
        ];
    }
}
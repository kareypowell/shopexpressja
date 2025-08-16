<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Package;

class VolumeCalculationService
{
    /**
     * Calculate total volume from a collection of packages
     *
     * @param Collection $packages
     * @return float Total volume in cubic feet
     */
    public function calculateTotalVolume(Collection $packages): float
    {
        return $packages->sum(function (Package $package) {
            // Use existing cubic_feet field if available, otherwise calculate from dimensions
            if (!is_null($package->cubic_feet) && $package->cubic_feet > 0) {
                return $package->cubic_feet;
            }

            return $this->calculateVolumeFromDimensions($package);
        });
    }

    /**
     * Calculate volume from package dimensions
     *
     * @param Package $package
     * @return float Volume in cubic feet
     */
    public function calculateVolumeFromDimensions(Package $package): float
    {
        if ($package->length_inches && $package->width_inches && $package->height_inches) {
            // Formula: (length × width × height) ÷ 1728 (cubic inches to cubic feet)
            return round(($package->length_inches * $package->width_inches * $package->height_inches) / 1728, 3);
        }

        return 0;
    }

    /**
     * Format volume for display
     *
     * @param float $cubicFeet Volume in cubic feet
     * @return string Formatted volume display
     */
    public function formatVolumeDisplay(float $cubicFeet): string
    {
        return number_format($cubicFeet, 2) . ' ft³';
    }

    /**
     * Get detailed volume formatting
     *
     * @param float $cubicFeet Volume in cubic feet
     * @return array Detailed volume display data
     */
    public function getVolumeDisplayData(float $cubicFeet): array
    {
        return [
            'cubic_feet' => number_format($cubicFeet, 2),
            'display' => $this->formatVolumeDisplay($cubicFeet),
            'raw_value' => $cubicFeet,
            'unit' => 'ft³'
        ];
    }

    /**
     * Validate volume data completeness in packages
     *
     * @param Collection $packages
     * @return array Validation results
     */
    public function validateVolumeData(Collection $packages): array
    {
        $totalPackages = $packages->count();
        $packagesWithVolume = $packages->filter(function (Package $package) {
            return $this->hasVolumeData($package);
        })->count();

        $missingVolumePackages = $packages->filter(function (Package $package) {
            return !$this->hasVolumeData($package);
        });

        return [
            'total_packages' => $totalPackages,
            'packages_with_volume' => $packagesWithVolume,
            'packages_missing_volume' => $totalPackages - $packagesWithVolume,
            'is_complete' => $packagesWithVolume === $totalPackages,
            'completion_percentage' => $totalPackages > 0 ? round(($packagesWithVolume / $totalPackages) * 100, 1) : 0,
            'missing_volume_tracking_numbers' => $missingVolumePackages->pluck('tracking_number')->toArray()
        ];
    }

    /**
     * Check if package has volume data
     *
     * @param Package $package
     * @return bool
     */
    public function hasVolumeData(Package $package): bool
    {
        // Check if cubic_feet is directly available
        if (!is_null($package->cubic_feet) && $package->cubic_feet > 0) {
            return true;
        }

        // Check if dimensions are available to calculate volume
        return !is_null($package->length_inches) && 
               !is_null($package->width_inches) && 
               !is_null($package->height_inches) &&
               $package->length_inches > 0 &&
               $package->width_inches > 0 &&
               $package->height_inches > 0;
    }

    /**
     * Get volume statistics for a collection of packages
     *
     * @param Collection $packages
     * @return array Volume statistics
     */
    public function getVolumeStatistics(Collection $packages): array
    {
        $packagesWithVolume = $packages->filter(function (Package $package) {
            return $this->hasVolumeData($package);
        });

        if ($packagesWithVolume->isEmpty()) {
            return [
                'total_volume' => 0,
                'average_volume' => 0,
                'min_volume' => 0,
                'max_volume' => 0,
                'formatted' => $this->getVolumeDisplayData(0)
            ];
        }

        $volumes = $packagesWithVolume->map(function (Package $package) {
            if (!is_null($package->cubic_feet) && $package->cubic_feet > 0) {
                return $package->cubic_feet;
            }
            return $this->calculateVolumeFromDimensions($package);
        });

        $totalVolume = $volumes->sum();
        $averageVolume = $volumes->avg();
        $minVolume = $volumes->min();
        $maxVolume = $volumes->max();

        return [
            'total_volume' => $totalVolume,
            'average_volume' => round($averageVolume, 3),
            'min_volume' => $minVolume,
            'max_volume' => $maxVolume,
            'formatted' => $this->getVolumeDisplayData($totalVolume)
        ];
    }

    /**
     * Estimate volume from weight for packages missing dimensions
     * This is a fallback method using industry averages
     *
     * @param Package $package
     * @return float Estimated volume in cubic feet
     */
    public function estimateVolumeFromWeight(Package $package): float
    {
        if (is_null($package->weight) || $package->weight <= 0) {
            return 0;
        }

        // Industry average: approximately 10-12 lbs per cubic foot for general cargo
        // Using 11 lbs per cubic foot as a reasonable estimate
        return round($package->weight / 11, 3);
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Package;
use App\Services\ManifestQueryOptimizationService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class VolumeCalculationService
{
    /**
     * Calculate total volume from a collection of packages
     *
     * @param Collection $packages
     * @return float Total volume in cubic feet
     * @throws InvalidArgumentException
     */
    public function calculateTotalVolume(Collection $packages): float
    {
        try {
            $this->validatePackagesCollection($packages);
            
            $totalVolume = $packages->sum(function (Package $package) {
                $volume = 0;
                
                // Use existing cubic_feet field if available, otherwise calculate from dimensions
                if (!is_null($package->cubic_feet) && is_numeric($package->cubic_feet) && $package->cubic_feet > 0) {
                    $volume = $package->cubic_feet;
                } else {
                    $volume = $this->calculateVolumeFromDimensions($package);
                }
                
                // Validate volume value
                if (!is_numeric($volume) || $volume < 0) {
                    Log::warning('Invalid volume value found in package', [
                        'package_id' => $package->id ?? null,
                        'tracking_number' => $package->tracking_number ?? null,
                        'volume' => $volume
                    ]);
                    return 0;
                }
                
                // Cap extremely high volumes (likely data errors)
                if ($volume > 1000) {
                    Log::warning('Extremely high volume value found, capping at 1000 ft³', [
                        'package_id' => $package->id ?? null,
                        'tracking_number' => $package->tracking_number ?? null,
                        'original_volume' => $volume
                    ]);
                    return 1000;
                }
                
                return $volume;
            });
            
            return round($totalVolume, 3);
        } catch (\Exception $e) {
            Log::error('Failed to calculate total volume', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage()
            ]);
            
            return 0.0;
        }
    }

    /**
     * Calculate volume from package dimensions
     *
     * @param Package $package
     * @return float Volume in cubic feet
     */
    public function calculateVolumeFromDimensions(Package $package): float
    {
        try {
            $length = $package->length_inches ?? 0;
            $width = $package->width_inches ?? 0;
            $height = $package->height_inches ?? 0;
            
            // Validate dimensions
            if (!is_numeric($length) || !is_numeric($width) || !is_numeric($height)) {
                return 0;
            }
            
            if ($length <= 0 || $width <= 0 || $height <= 0) {
                return 0;
            }
            
            // Check for reasonable dimension limits (max 120 inches per dimension)
            if ($length > 120 || $width > 120 || $height > 120) {
                Log::warning('Extremely large package dimensions found', [
                    'package_id' => $package->id ?? null,
                    'tracking_number' => $package->tracking_number ?? null,
                    'dimensions' => "{$length}x{$width}x{$height}"
                ]);
                
                // Cap dimensions at reasonable maximums
                $length = min($length, 120);
                $width = min($width, 120);
                $height = min($height, 120);
            }
            
            // Formula: (length × width × height) ÷ 1728 (cubic inches to cubic feet)
            $volume = ($length * $width * $height) / 1728;
            
            return round($volume, 3);
        } catch (\Exception $e) {
            Log::error('Failed to calculate volume from dimensions', [
                'package_id' => $package->id ?? null,
                'tracking_number' => $package->tracking_number ?? null,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
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
        try {
            $this->validatePackagesCollection($packages);
            
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
                $volume = 0;
                
                if (!is_null($package->cubic_feet) && is_numeric($package->cubic_feet) && $package->cubic_feet > 0) {
                    $volume = $package->cubic_feet;
                } else {
                    $volume = $this->calculateVolumeFromDimensions($package);
                }
                
                return $volume;
            })->filter(function ($volume) {
                return is_numeric($volume) && $volume > 0 && $volume <= 1000;
            });

            if ($volumes->isEmpty()) {
                Log::warning('No valid volumes found after filtering');
                return [
                    'total_volume' => 0,
                    'average_volume' => 0,
                    'min_volume' => 0,
                    'max_volume' => 0,
                    'formatted' => $this->getVolumeDisplayData(0)
                ];
            }

            $totalVolume = $volumes->sum();
            $averageVolume = $volumes->avg();
            $minVolume = $volumes->min();
            $maxVolume = $volumes->max();

            return [
                'total_volume' => round($totalVolume, 3),
                'average_volume' => round($averageVolume, 3),
                'min_volume' => $minVolume,
                'max_volume' => $maxVolume,
                'formatted' => $this->getVolumeDisplayData($totalVolume)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to calculate volume statistics', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_volume' => 0,
                'average_volume' => 0,
                'min_volume' => 0,
                'max_volume' => 0,
                'formatted' => $this->getVolumeDisplayData(0)
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
                } elseif ($value > 100000) {
                    Log::warning("Extremely high value found in calculation results: {$key} = {$value}");
                    $validatedResults[$key] = 100000; // Cap at reasonable maximum
                } else {
                    $validatedResults[$key] = $value;
                }
            } else {
                $validatedResults[$key] = $value;
            }
        }
        
        return $validatedResults;
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
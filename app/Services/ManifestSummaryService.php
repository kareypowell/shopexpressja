<?php

namespace App\Services;

use App\Models\Manifest;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ManifestSummaryException;
use App\Exceptions\DataValidationException;
use App\Exceptions\ServiceUnavailableException;
use App\Exceptions\CalculationException;
use InvalidArgumentException;

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
     * @throws ManifestSummaryException
     */
    public function getManifestSummary(Manifest $manifest): array
    {
        $manifestId = $manifest->id ?? null;
        $startTime = microtime(true);
        
        try {
            // Comprehensive input validation
            $validationResult = $this->validateManifestInput($manifest);
            if (!$validationResult['valid']) {
                throw new DataValidationException(
                    $validationResult['message'],
                    0,
                    null,
                    $validationResult['errors'] ?? [],
                    'manifest'
                );
            }

            $packages = $manifest->packages;
            
            // Validate packages collection
            $packagesValidation = $this->validatePackagesCollection($packages);
            if (!$packagesValidation['valid']) {
                Log::warning('Packages collection validation failed', [
                    'manifest_id' => $manifestId,
                    'errors' => $packagesValidation['errors'],
                    'package_count' => $packagesValidation['package_count'] ?? 0
                ]);
                
                // If validation fails critically, throw exception
                if (count($packagesValidation['errors']) > 5) {
                    throw new DataValidationException(
                        'Critical packages collection validation failure',
                        0,
                        null,
                        $packagesValidation['errors'],
                        'packages_collection'
                    );
                }
            }

            $manifestType = $this->getManifestType($manifest);

            $baseSummary = [
                'manifest_type' => $manifestType,
                'package_count' => max(0, $packages->count()),
                'total_value' => $this->calculateTotalValue($packages),
            ];

            if ($manifestType === 'air') {
                $airSummary = $this->calculateAirManifestSummary($packages);
                $result = array_merge($baseSummary, $this->validateSummaryData($airSummary));
            } elseif ($manifestType === 'sea') {
                $seaSummary = $this->calculateSeaManifestSummary($packages);
                $result = array_merge($baseSummary, $this->validateSummaryData($seaSummary));
            } else {
                // Fallback for unknown manifest types - include both weight and volume
                $airSummary = $this->calculateAirManifestSummary($packages);
                $seaSummary = $this->calculateSeaManifestSummary($packages);
                
                $result = array_merge(
                    $baseSummary,
                    $this->validateSummaryData($airSummary),
                    $this->validateSummaryData($seaSummary)
                );
            }
            
            // Log successful calculation performance
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('Manifest summary calculated successfully', [
                'manifest_id' => $manifestId,
                'manifest_type' => $manifestType,
                'package_count' => $baseSummary['package_count'],
                'execution_time_ms' => round($executionTime, 2)
            ]);
            
            return $result;
            
        } catch (DataValidationException $e) {
            $this->logCalculationError($manifestId, 'validation', $e, $startTime);
            return $this->getFallbackSummary($manifest);
            
        } catch (CalculationException $e) {
            $this->logCalculationError($manifestId, 'calculation', $e, $startTime);
            return $this->getFallbackSummary($manifest);
            
        } catch (ServiceUnavailableException $e) {
            $this->logCalculationError($manifestId, 'service', $e, $startTime);
            return $this->getFallbackSummary($manifest);
            
        } catch (InvalidArgumentException $e) {
            $this->logCalculationError($manifestId, 'argument', $e, $startTime);
            throw new ManifestSummaryException(
                'Invalid input provided for manifest summary calculation',
                0,
                $e,
                $manifestId,
                ['validation_error' => $e->getMessage()]
            );
            
        } catch (\Exception $e) {
            $this->logCalculationError($manifestId, 'general', $e, $startTime);
            return $this->getFallbackSummary($manifest);
        }
    }

    /**
     * Validate manifest input before processing
     */
    protected function validateManifestInput($manifest): array
    {
        $errors = [];
        
        // Check if manifest object exists
        if (!$manifest) {
            return ['valid' => false, 'message' => 'Manifest object is null'];
        }
        
        // Validate manifest ID
        if (!isset($manifest->id) || !is_numeric($manifest->id) || $manifest->id <= 0) {
            $errors[] = 'Invalid manifest ID';
        }
        
        // Validate manifest has required timestamps
        if (!isset($manifest->created_at)) {
            $errors[] = 'Manifest missing created_at timestamp';
        }
        
        // Validate manifest type if present
        if (isset($manifest->type) && !empty($manifest->type)) {
            $validTypes = ['air', 'sea'];
            if (!in_array(strtolower($manifest->type), $validTypes)) {
                $errors[] = "Invalid manifest type: {$manifest->type}";
            }
        }
        
        return empty($errors) ? 
            ['valid' => true, 'message' => 'Manifest validation passed'] :
            ['valid' => false, 'message' => implode('; ', $errors), 'errors' => $errors];
    }
    
    /**
     * Validate packages collection for data integrity
     */
    protected function validatePackagesCollection($packages): array
    {
        $errors = [];
        
        try {
            if ($packages === null) {
                return ['valid' => false, 'errors' => ['Packages collection is null']];
            }
            
            $packageCount = $packages->count();
            
            // Validate reasonable package count
            if ($packageCount < 0) {
                $errors[] = 'Package count cannot be negative';
            } elseif ($packageCount > 10000) {
                $errors[] = 'Package count exceeds reasonable limit (10,000)';
            }
            
            // Sample validation for large collections
            $sampleSize = min($packageCount, 10);
            $validatedPackages = 0;
            
            foreach ($packages->take($sampleSize) as $package) {
                if ($this->validatePackageObject($package)) {
                    $validatedPackages++;
                }
            }
            
            // If sample validation fails significantly, flag as invalid
            if ($sampleSize > 0 && ($validatedPackages / $sampleSize) < 0.5) {
                $errors[] = 'More than 50% of sampled packages failed validation';
            }
            
        } catch (\Exception $e) {
            $errors[] = "Failed to validate packages collection: {$e->getMessage()}";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'package_count' => $packageCount ?? 0
        ];
    }
    
    /**
     * Validate individual package object
     */
    protected function validatePackageObject($package): bool
    {
        try {
            // Check basic package structure
            if (!$package || !isset($package->id)) {
                return false;
            }
            
            // Validate package ID
            if (!is_numeric($package->id) || $package->id <= 0) {
                return false;
            }
            
            // Validate numeric fields if present
            $numericFields = ['freight_price', 'clearance_fee', 'storage_fee', 'delivery_fee', 'weight'];
            foreach ($numericFields as $field) {
                if (isset($package->$field) && !$this->isValidNumericValue($package->$field)) {
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate numeric value for reasonable ranges
     */
    protected function isValidNumericValue($value): bool
    {
        if ($value === null || $value === '') {
            return true; // Null/empty values are acceptable
        }
        
        if (!is_numeric($value)) {
            return false;
        }
        
        $numericValue = (float) $value;
        
        // Check for reasonable ranges
        if ($numericValue < -1000000 || $numericValue > 1000000) {
            return false;
        }
        
        // Check for NaN or infinite values
        if (!is_finite($numericValue)) {
            return false;
        }
        
        return true;
    }

    /**
     * Calculate summary for Air manifests (weight-focused)
     *
     * @param Collection $packages
     * @return array Air manifest summary data
     * @throws CalculationException
     * @throws ServiceUnavailableException
     */
    public function calculateAirManifestSummary(Collection $packages): array
    {
        $startTime = microtime(true);
        
        try {
            // Input validation
            $this->validatePackagesInput($packages, 'air');
            
            // Validate packages collection before processing
            if ($packages->isEmpty()) {
                return $this->getEmptyAirSummary($packages->count());
            }

            // Check if weight service is available
            if (!$this->isWeightServiceAvailable()) {
                throw new ServiceUnavailableException(
                    'Weight calculation service is unavailable',
                    503,
                    null,
                    'WeightCalculationService',
                    30
                );
            }

            $weightStats = $this->getWeightStatisticsSafely($packages);
            $weightValidation = $this->getWeightValidationSafely($packages);

            // Validate weight statistics
            $weightStats = $this->validateCalculationResults($weightStats, 'weight');

            $result = [
                'weight' => [
                    'total_lbs' => $this->sanitizeNumericValue($weightStats['total_weight_lbs'] ?? 0),
                    'total_kg' => $this->sanitizeNumericValue($weightStats['total_weight_kg'] ?? 0),
                    'average_lbs' => $this->sanitizeNumericValue($weightStats['average_weight_lbs'] ?? 0),
                    'average_kg' => $this->sanitizeNumericValue($weightStats['average_weight_kg'] ?? 0),
                    'formatted' => $weightStats['formatted'] ?? $this->getDefaultWeightFormat()
                ],
                'weight_validation' => $this->sanitizeValidationData($weightValidation),
                'incomplete_data' => !($weightValidation['is_complete'] ?? false),
                'primary_metric' => 'weight'
            ];
            
            // Log successful calculation
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::debug('Air manifest summary calculated', [
                'package_count' => $packages->count(),
                'execution_time_ms' => round($executionTime, 2),
                'total_weight_lbs' => $result['weight']['total_lbs']
            ]);
            
            return $result;
            
        } catch (ServiceUnavailableException $e) {
            throw $e; // Re-throw service exceptions
            
        } catch (DataValidationException $e) {
            throw new CalculationException(
                'Air manifest calculation failed due to validation errors',
                0,
                $e,
                'air_manifest',
                ['package_count' => $packages->count(), 'validation_errors' => $e->getValidationErrors()]
            );
            
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('Failed to calculate air manifest summary', [
                'package_count' => $packages->count(),
                'execution_time_ms' => round($executionTime, 2),
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new CalculationException(
                'Air manifest calculation failed: ' . $e->getMessage(),
                0,
                $e,
                'air_manifest',
                ['package_count' => $packages->count()]
            );
        }
    }
    
    /**
     * Get empty air summary for fallback
     */
    protected function getEmptyAirSummary(int $packageCount): array
    {
        return [
            'weight' => [
                'total_lbs' => 0,
                'total_kg' => 0,
                'average_lbs' => 0,
                'average_kg' => 0,
                'formatted' => $this->weightService->formatWeightUnits(0)
            ],
            'weight_validation' => [
                'total_packages' => $packageCount,
                'packages_with_weight' => 0,
                'packages_missing_weight' => $packageCount,
                'is_complete' => false,
                'completion_percentage' => 0,
                'missing_weight_tracking_numbers' => []
            ],
            'incomplete_data' => true,
            'primary_metric' => 'weight'
        ];
    }

    /**
     * Calculate summary for Sea manifests (volume-focused)
     *
     * @param Collection $packages
     * @return array Sea manifest summary data
     * @throws CalculationException
     * @throws ServiceUnavailableException
     */
    public function calculateSeaManifestSummary(Collection $packages): array
    {
        $startTime = microtime(true);
        
        try {
            // Input validation
            $this->validatePackagesInput($packages, 'sea');
            
            // Validate packages collection before processing
            if ($packages->isEmpty()) {
                return $this->getEmptySeaSummary($packages->count());
            }

            // Check if volume service is available
            if (!$this->isVolumeServiceAvailable()) {
                throw new ServiceUnavailableException(
                    'Volume calculation service is unavailable',
                    503,
                    null,
                    'VolumeCalculationService',
                    30
                );
            }

            $volumeStats = $this->getVolumeStatisticsSafely($packages);
            $volumeValidation = $this->getVolumeValidationSafely($packages);

            // Validate volume statistics
            $volumeStats = $this->validateCalculationResults($volumeStats, 'volume');

            $result = [
                'volume' => [
                    'total_cubic_feet' => $this->sanitizeNumericValue($volumeStats['total_volume'] ?? 0),
                    'average_cubic_feet' => $this->sanitizeNumericValue($volumeStats['average_volume'] ?? 0),
                    'formatted' => $volumeStats['formatted'] ?? $this->getDefaultVolumeFormat()
                ],
                'volume_validation' => $this->sanitizeValidationData($volumeValidation),
                'incomplete_data' => !($volumeValidation['is_complete'] ?? false),
                'primary_metric' => 'volume'
            ];
            
            // Log successful calculation
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::debug('Sea manifest summary calculated', [
                'package_count' => $packages->count(),
                'execution_time_ms' => round($executionTime, 2),
                'total_volume' => $result['volume']['total_cubic_feet']
            ]);
            
            return $result;
            
        } catch (ServiceUnavailableException $e) {
            throw $e; // Re-throw service exceptions
            
        } catch (DataValidationException $e) {
            throw new CalculationException(
                'Sea manifest calculation failed due to validation errors',
                0,
                $e,
                'sea_manifest',
                ['package_count' => $packages->count(), 'validation_errors' => $e->getValidationErrors()]
            );
            
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('Failed to calculate sea manifest summary', [
                'package_count' => $packages->count(),
                'execution_time_ms' => round($executionTime, 2),
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new CalculationException(
                'Sea manifest calculation failed: ' . $e->getMessage(),
                0,
                $e,
                'sea_manifest',
                ['package_count' => $packages->count()]
            );
        }
    }
    
    /**
     * Get empty sea summary for fallback
     */
    protected function getEmptySeaSummary(int $packageCount): array
    {
        return [
            'volume' => [
                'total_cubic_feet' => 0,
                'average_cubic_feet' => 0,
                'formatted' => $this->volumeService->getVolumeDisplayData(0)
            ],
            'volume_validation' => [
                'total_packages' => $packageCount,
                'packages_with_volume' => 0,
                'packages_missing_volume' => $packageCount,
                'is_complete' => false,
                'completion_percentage' => 0,
                'missing_volume_tracking_numbers' => []
            ],
            'incomplete_data' => true,
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
     * Sanitize numeric value to ensure it's within reasonable bounds
     */
    protected function sanitizeNumericValue($value, float $min = 0, float $max = 1000000): float
    {
        if ($value === null || $value === '') {
            return $min;
        }
        
        if (!is_numeric($value)) {
            return $min;
        }
        
        $numericValue = (float) $value;
        
        // Check for NaN or infinite values
        if (!is_finite($numericValue)) {
            return $min;
        }
        
        // Clamp to valid range
        return max($min, min($max, $numericValue));
    }
    
    /**
     * Sanitize validation data to ensure safe structure
     */
    protected function sanitizeValidationData($validationData): array
    {
        if (!is_array($validationData)) {
            return [
                'total_packages' => 0,
                'is_complete' => false,
                'completion_percentage' => 0
            ];
        }
        
        return [
            'total_packages' => $this->sanitizeNumericValue($validationData['total_packages'] ?? 0, 0, 10000),
            'packages_with_weight' => $this->sanitizeNumericValue($validationData['packages_with_weight'] ?? 0, 0, 10000),
            'packages_missing_weight' => $this->sanitizeNumericValue($validationData['packages_missing_weight'] ?? 0, 0, 10000),
            'packages_with_volume' => $this->sanitizeNumericValue($validationData['packages_with_volume'] ?? 0, 0, 10000),
            'packages_missing_volume' => $this->sanitizeNumericValue($validationData['packages_missing_volume'] ?? 0, 0, 10000),
            'is_complete' => (bool) ($validationData['is_complete'] ?? false),
            'completion_percentage' => $this->sanitizeNumericValue($validationData['completion_percentage'] ?? 0, 0, 100),
            'missing_weight_tracking_numbers' => is_array($validationData['missing_weight_tracking_numbers'] ?? null) ? 
                array_slice($validationData['missing_weight_tracking_numbers'], 0, 100) : [],
            'missing_volume_tracking_numbers' => is_array($validationData['missing_volume_tracking_numbers'] ?? null) ? 
                array_slice($validationData['missing_volume_tracking_numbers'], 0, 100) : []
        ];
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
                // Safely extract and validate cost components
                $freightPrice = $this->sanitizeNumericValue($package->freight_price ?? 0, 0, 100000);
                $clearanceFee = $this->sanitizeNumericValue($package->clearance_fee ?? 0, 0, 100000);
                $storageFee = $this->sanitizeNumericValue($package->storage_fee ?? 0, 0, 100000);
                $deliveryFee = $this->sanitizeNumericValue($package->delivery_fee ?? 0, 0, 100000);
                
                $cost = $freightPrice + $clearanceFee + $storageFee + $deliveryFee;
                
                // Additional validation for extremely high individual package costs
                if ($cost > 50000) {
                    Log::warning('Extremely high cost value found in package', [
                        'package_id' => $package->id ?? null,
                        'tracking_number' => $package->tracking_number ?? null,
                        'cost' => $cost,
                        'freight_price' => $freightPrice,
                        'clearance_fee' => $clearanceFee,
                        'storage_fee' => $storageFee,
                        'delivery_fee' => $deliveryFee
                    ]);
                }
                
                return $cost;
            });
            
            return round($this->sanitizeNumericValue($totalValue, 0, 10000000), 2);
        } catch (\Exception $e) {
            Log::error('Failed to calculate total value', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage(),
                'error_class' => get_class($e)
            ]);
            
            return 0.0;
        }
    }

    /**
     * Validate summary data before returning
     *
     * @param array $summaryData
     * @return array Validated summary data
     * @throws DataValidationException
     */
    protected function validateSummaryData(array $summaryData): array
    {
        try {
            $validatedData = [];
            $validationErrors = [];
            
            foreach ($summaryData as $key => $value) {
                try {
                    if (is_array($value)) {
                        $validatedData[$key] = $this->validateSummaryData($value);
                    } elseif (is_numeric($value)) {
                        // Sanitize numeric values with appropriate ranges
                        $sanitized = $this->sanitizeNumericValue($value);
                        if ($sanitized !== $value && abs($value - $sanitized) > 0.01) {
                            $validationErrors[] = "Numeric value '{$key}' was sanitized from {$value} to {$sanitized}";
                        }
                        $validatedData[$key] = $sanitized;
                    } elseif (is_string($value)) {
                        // Sanitize string values
                        $sanitized = $this->sanitizeStringValue($value);
                        if ($sanitized !== $value) {
                            $validationErrors[] = "String value '{$key}' was sanitized";
                        }
                        $validatedData[$key] = $sanitized;
                    } elseif (is_bool($value)) {
                        $validatedData[$key] = $value;
                    } else {
                        // Convert other types to string and sanitize
                        $stringValue = (string) $value;
                        $validatedData[$key] = $this->sanitizeStringValue($stringValue);
                        $validationErrors[] = "Value '{$key}' was converted from " . gettype($value) . " to string";
                    }
                } catch (\Exception $e) {
                    $validationErrors[] = "Failed to validate key '{$key}': " . $e->getMessage();
                    // Provide safe fallback value
                    $validatedData[$key] = is_numeric($value) ? 0 : '';
                }
            }
            
            // Log validation warnings if any
            if (!empty($validationErrors)) {
                Log::warning('Summary data validation warnings', [
                    'errors' => $validationErrors,
                    'data_keys' => array_keys($summaryData)
                ]);
            }
            
            return $validatedData;
            
        } catch (\Exception $e) {
            Log::error('Critical failure in summary data validation', [
                'error' => $e->getMessage(),
                'data_structure' => array_keys($summaryData)
            ]);
            
            throw new DataValidationException(
                'Failed to validate summary data structure',
                0,
                $e,
                ['validation_failure' => $e->getMessage()],
                'summary_data'
            );
        }
    }
    
    /**
     * Sanitize string values for safe output
     */
    protected function sanitizeStringValue(string $value, int $maxLength = 255): string
    {
        // Remove potentially harmful characters
        $sanitized = strip_tags($value);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        // Trim whitespace
        $sanitized = trim($sanitized);
        
        // Limit length
        if (strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength);
        }
        
        return $sanitized;
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
        try {
            $summary = $this->getManifestSummary($manifest);
            return !($summary['incomplete_data'] ?? true);
        } catch (\Exception $e) {
            Log::error('Failed to check manifest data completeness', [
                'manifest_id' => $manifest->id ?? null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate packages input for specific calculation type
     *
     * @param Collection $packages
     * @param string $calculationType
     * @throws DataValidationException
     */
    protected function validatePackagesInput(Collection $packages, string $calculationType): void
    {
        $errors = [];
        
        try {
            // Basic collection validation
            if (!($packages instanceof Collection)) {
                throw new DataValidationException(
                    'Packages must be a Collection instance',
                    0,
                    null,
                    ['type_error' => 'Expected Collection, got ' . gettype($packages)],
                    'packages_input'
                );
            }
            
            // Check collection size limits
            $packageCount = $packages->count();
            if ($packageCount > 10000) {
                $errors[] = "Package count ({$packageCount}) exceeds maximum limit (10,000)";
            }
            
            // Sample validation for non-empty collections
            if ($packageCount > 0) {
                $sampleSize = min($packageCount, 5);
                $invalidPackages = 0;
                
                foreach ($packages->take($sampleSize) as $index => $package) {
                    if (!($package instanceof Package)) {
                        $invalidPackages++;
                        $errors[] = "Item at index {$index} is not a Package instance";
                    } elseif (!$this->validatePackageObject($package)) {
                        $invalidPackages++;
                        $errors[] = "Package at index {$index} failed validation";
                    }
                }
                
                // If more than half the sample is invalid, fail validation
                if ($sampleSize > 0 && ($invalidPackages / $sampleSize) > 0.5) {
                    $errors[] = "More than 50% of sampled packages are invalid";
                }
            }
            
            if (!empty($errors)) {
                throw new DataValidationException(
                    "Packages validation failed for {$calculationType} calculation",
                    0,
                    null,
                    $errors,
                    'packages_' . $calculationType
                );
            }
            
        } catch (DataValidationException $e) {
            throw $e; // Re-throw validation exceptions
        } catch (\Exception $e) {
            throw new DataValidationException(
                'Unexpected error during packages validation',
                0,
                $e,
                ['unexpected_error' => $e->getMessage()],
                'packages_validation'
            );
        }
    }

    /**
     * Check if weight service is available and functional
     *
     * @return bool
     */
    protected function isWeightServiceAvailable(): bool
    {
        try {
            // Test basic service functionality
            $testCollection = collect([]);
            $this->weightService->getWeightStatistics($testCollection);
            return true;
        } catch (\Exception $e) {
            Log::warning('Weight service availability check failed', [
                'error' => $e->getMessage(),
                'service' => 'WeightCalculationService'
            ]);
            return false;
        }
    }

    /**
     * Check if volume service is available and functional
     *
     * @return bool
     */
    protected function isVolumeServiceAvailable(): bool
    {
        try {
            // Test basic service functionality
            $testCollection = collect([]);
            $this->volumeService->getVolumeStatistics($testCollection);
            return true;
        } catch (\Exception $e) {
            Log::warning('Volume service availability check failed', [
                'error' => $e->getMessage(),
                'service' => 'VolumeCalculationService'
            ]);
            return false;
        }
    }

    /**
     * Get weight statistics with error handling
     *
     * @param Collection $packages
     * @return array
     * @throws ServiceUnavailableException
     */
    protected function getWeightStatisticsSafely(Collection $packages): array
    {
        try {
            return $this->weightService->getWeightStatistics($packages);
        } catch (\Exception $e) {
            Log::error('Weight statistics calculation failed', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage(),
                'service' => 'WeightCalculationService'
            ]);
            
            throw new ServiceUnavailableException(
                'Weight statistics service failed: ' . $e->getMessage(),
                503,
                $e,
                'WeightCalculationService'
            );
        }
    }

    /**
     * Get weight validation with error handling
     *
     * @param Collection $packages
     * @return array
     * @throws ServiceUnavailableException
     */
    protected function getWeightValidationSafely(Collection $packages): array
    {
        try {
            return $this->weightService->validateWeightData($packages);
        } catch (\Exception $e) {
            Log::error('Weight validation failed', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage(),
                'service' => 'WeightCalculationService'
            ]);
            
            // Return safe fallback validation data
            return [
                'total_packages' => $packages->count(),
                'packages_with_weight' => 0,
                'packages_missing_weight' => $packages->count(),
                'is_complete' => false,
                'completion_percentage' => 0,
                'missing_weight_tracking_numbers' => []
            ];
        }
    }

    /**
     * Get volume statistics with error handling
     *
     * @param Collection $packages
     * @return array
     * @throws ServiceUnavailableException
     */
    protected function getVolumeStatisticsSafely(Collection $packages): array
    {
        try {
            return $this->volumeService->getVolumeStatistics($packages);
        } catch (\Exception $e) {
            Log::error('Volume statistics calculation failed', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage(),
                'service' => 'VolumeCalculationService'
            ]);
            
            throw new ServiceUnavailableException(
                'Volume statistics service failed: ' . $e->getMessage(),
                503,
                $e,
                'VolumeCalculationService'
            );
        }
    }

    /**
     * Get volume validation with error handling
     *
     * @param Collection $packages
     * @return array
     * @throws ServiceUnavailableException
     */
    protected function getVolumeValidationSafely(Collection $packages): array
    {
        try {
            return $this->volumeService->validateVolumeData($packages);
        } catch (\Exception $e) {
            Log::error('Volume validation failed', [
                'package_count' => $packages->count(),
                'error' => $e->getMessage(),
                'service' => 'VolumeCalculationService'
            ]);
            
            // Return safe fallback validation data
            return [
                'total_packages' => $packages->count(),
                'packages_with_volume' => 0,
                'packages_missing_volume' => $packages->count(),
                'is_complete' => false,
                'completion_percentage' => 0,
                'missing_volume_tracking_numbers' => []
            ];
        }
    }

    /**
     * Validate calculation results with enhanced error checking
     *
     * @param array $results
     * @param string $calculationType
     * @return array
     * @throws CalculationException
     */
    protected function validateCalculationResults(array $results, string $calculationType): array
    {
        try {
            if ($calculationType === 'weight') {
                return $this->weightService->validateCalculationResults($results);
            } elseif ($calculationType === 'volume') {
                return $this->volumeService->validateCalculationResults($results);
            }
            
            // Generic validation for unknown types
            $validatedResults = [];
            foreach ($results as $key => $value) {
                if (is_numeric($value)) {
                    $validatedResults[$key] = $this->sanitizeNumericValue($value);
                } else {
                    $validatedResults[$key] = $value;
                }
            }
            
            return $validatedResults;
            
        } catch (\Exception $e) {
            Log::error('Calculation results validation failed', [
                'calculation_type' => $calculationType,
                'error' => $e->getMessage(),
                'results_keys' => array_keys($results)
            ]);
            
            throw new CalculationException(
                "Failed to validate {$calculationType} calculation results",
                0,
                $e,
                $calculationType,
                ['results_structure' => array_keys($results)]
            );
        }
    }

    /**
     * Get default weight format for fallback scenarios
     *
     * @return array
     */
    protected function getDefaultWeightFormat(): array
    {
        try {
            return $this->weightService->formatWeightUnits(0);
        } catch (\Exception $e) {
            Log::warning('Failed to get default weight format', ['error' => $e->getMessage()]);
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
     * Get default volume format for fallback scenarios
     *
     * @return array
     */
    protected function getDefaultVolumeFormat(): array
    {
        try {
            return $this->volumeService->getVolumeDisplayData(0);
        } catch (\Exception $e) {
            Log::warning('Failed to get default volume format', ['error' => $e->getMessage()]);
            return [
                'cubic_feet' => '0.00',
                'display' => '0.00 ft³',
                'raw_value' => 0.0,
                'unit' => 'ft³'
            ];
        }
    }

    /**
     * Log calculation errors with detailed context
     *
     * @param int|null $manifestId
     * @param string $errorType
     * @param \Exception $exception
     * @param float $startTime
     */
    protected function logCalculationError(?int $manifestId, string $errorType, \Exception $exception, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        $context = [
            'manifest_id' => $manifestId,
            'error_type' => $errorType,
            'error_class' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'execution_time_ms' => round($executionTime, 2),
            'user_id' => auth()->id(),
            'request_id' => request()->header('X-Request-ID'),
            'timestamp' => now()->toISOString()
        ];
        
        // Add exception-specific context
        if ($exception instanceof ManifestSummaryException) {
            $context['exception_context'] = $exception->getContext();
        } elseif ($exception instanceof DataValidationException) {
            $context['validation_errors'] = $exception->getValidationErrors();
            $context['data_type'] = $exception->getDataType();
        } elseif ($exception instanceof ServiceUnavailableException) {
            $context['service_name'] = $exception->getServiceName();
            $context['retry_after'] = $exception->getRetryAfter();
        } elseif ($exception instanceof CalculationException) {
            $context['calculation_type'] = $exception->getCalculationType();
            $context['input_data'] = $exception->getInputData();
        }
        
        // Include stack trace for debugging (truncated for log size)
        $context['stack_trace'] = substr($exception->getTraceAsString(), 0, 2000);
        
        Log::error('Manifest summary calculation error', $context);
    }
}
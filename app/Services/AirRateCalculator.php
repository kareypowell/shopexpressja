<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Rate;
use App\Exceptions\AirRateNotFoundException;
use InvalidArgumentException;

class AirRateCalculator
{
    /**
     * Calculate freight price for air packages using weight
     *
     * @param Package $package
     * @return float
     * @throws InvalidArgumentException
     */
    public function calculateFreightPrice(Package $package): float
    {
        // Validate that this is an air package
        if (!$package->isAirPackage()) {
            throw new InvalidArgumentException('Package must belong to an air manifest');
        }

        // Ensure weight is available and valid
        $weight = $package->weight;
        if ($weight <= 0) {
            throw new InvalidArgumentException('Package must have valid weight greater than 0');
        }

        // Round up weight to nearest whole number (standard air freight practice)
        $roundedWeight = ceil($weight);

        // Find appropriate rate for the weight
        $rate = $this->findRateForWeight($roundedWeight);

        // Get exchange rate from manifest (fallback to 1 if null or 0)
        $exchangeRate = $package->manifest->exchange_rate;
        if (empty($exchangeRate) || $exchangeRate <= 0) {
            $exchangeRate = 1;
        }

        // Calculate total price: (rate price + processing fee) * exchange rate
        return ($rate->price + $rate->processing_fee) * $exchangeRate;
    }

    /**
     * Find the appropriate rate for given weight
     *
     * @param float $weight
     * @return Rate
     * @throws AirRateNotFoundException
     */
    protected function findRateForWeight(float $weight): Rate
    {
        // First try to find exact weight match
        $rate = Rate::forAirShipment($weight)->first();

        if ($rate) {
            return $rate;
        }

        // Fallback: try to find any air rate that matches the weight (for backward compatibility)
        $fallbackRate = Rate::where('type', 'air')
            ->where('weight', $weight)
            ->first();

        if ($fallbackRate) {
            return $fallbackRate;
        }

        // Final fallback: find the closest higher weight rate
        $higherWeightRate = Rate::where('type', 'air')
            ->where('weight', '>=', $weight)
            ->orderBy('weight', 'asc')
            ->first();

        if ($higherWeightRate) {
            return $higherWeightRate;
        }

        // No air rates found at all
        throw new AirRateNotFoundException($weight);
    }

    /**
     * Get rate breakdown for transparency
     *
     * @param Package $package
     * @return array
     */
    public function getRateBreakdown(Package $package): array
    {
        if (!$package->isAirPackage()) {
            throw new InvalidArgumentException('Package must belong to an air manifest');
        }

        $weight = $package->weight;
        if ($weight <= 0) {
            throw new InvalidArgumentException('Package must have valid weight greater than 0');
        }

        $roundedWeight = ceil($weight);
        $rate = $this->findRateForWeight($roundedWeight);
        $exchangeRate = $package->manifest->exchange_rate;
        if (empty($exchangeRate) || $exchangeRate <= 0) {
            $exchangeRate = 1;
        }

        $basePrice = $rate->price;
        $processingFee = $rate->processing_fee;
        $subtotal = $basePrice + $processingFee;
        $total = $subtotal * $exchangeRate;

        return [
            'weight' => $weight,
            'rounded_weight' => $roundedWeight,
            'rate_weight' => $rate->weight,
            'base_price' => $basePrice,
            'processing_fee' => $processingFee,
            'subtotal' => $subtotal,
            'exchange_rate' => $exchangeRate,
            'total' => $total,
        ];
    }
}
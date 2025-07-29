<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Rate;
use InvalidArgumentException;

class SeaRateCalculator
{
    /**
     * Calculate freight price for sea packages using cubic feet
     *
     * @param Package $package
     * @return float
     * @throws InvalidArgumentException
     */
    public function calculateFreightPrice(Package $package): float
    {
        // Validate that this is a sea package
        if (!$package->isSeaPackage()) {
            throw new InvalidArgumentException('Package must belong to a sea manifest');
        }

        // Ensure cubic feet is calculated and available
        $cubicFeet = $package->cubic_feet;
        if ($cubicFeet <= 0) {
            throw new InvalidArgumentException('Package must have valid cubic feet greater than 0');
        }

        // Find appropriate rate for the cubic feet
        $rate = $this->findRateForCubicFeet($cubicFeet);

        // Get exchange rate from manifest (fallback to 1 if null or 0)
        $exchangeRate = $package->manifest->exchange_rate ?: 1;

        // Calculate total price: (rate per cubic foot * cubic feet + processing fee) * exchange rate
        return ($rate->price * $cubicFeet + $rate->processing_fee) * $exchangeRate;
    }

    /**
     * Find the appropriate rate for given cubic feet
     *
     * @param float $cubicFeet
     * @return Rate
     * @throws InvalidArgumentException
     */
    protected function findRateForCubicFeet(float $cubicFeet): Rate
    {
        // First try to find exact range match
        $rate = Rate::forSeaShipment($cubicFeet)->first();

        if ($rate) {
            return $rate;
        }

        // Fallback: try to find the closest rate with higher max_cubic_feet
        $fallbackRate = Rate::where('type', 'sea')
            ->where('max_cubic_feet', '>=', $cubicFeet)
            ->orderBy('max_cubic_feet', 'asc')
            ->first();

        if ($fallbackRate) {
            return $fallbackRate;
        }

        // Final fallback: use the highest range rate
        $highestRate = Rate::where('type', 'sea')
            ->orderBy('max_cubic_feet', 'desc')
            ->first();

        if ($highestRate) {
            return $highestRate;
        }

        // No sea rates found at all
        throw new InvalidArgumentException('No sea rates found in the system');
    }

    /**
     * Get rate breakdown for transparency
     *
     * @param Package $package
     * @return array
     */
    public function getRateBreakdown(Package $package): array
    {
        if (!$package->isSeaPackage()) {
            throw new InvalidArgumentException('Package must belong to a sea manifest');
        }

        $cubicFeet = $package->cubic_feet;
        if ($cubicFeet <= 0) {
            throw new InvalidArgumentException('Package must have valid cubic feet greater than 0');
        }

        $rate = $this->findRateForCubicFeet($cubicFeet);
        $exchangeRate = $package->manifest->exchange_rate ?: 1;

        $freightCost = $rate->price * $cubicFeet;
        $processingFee = $rate->processing_fee;
        $subtotal = $freightCost + $processingFee;
        $total = $subtotal * $exchangeRate;

        return [
            'cubic_feet' => $cubicFeet,
            'rate_per_cubic_foot' => $rate->price,
            'freight_cost' => $freightCost,
            'processing_fee' => $processingFee,
            'subtotal' => $subtotal,
            'exchange_rate' => $exchangeRate,
            'total' => $total,
            'rate_range' => [
                'min_cubic_feet' => $rate->min_cubic_feet,
                'max_cubic_feet' => $rate->max_cubic_feet,
            ]
        ];
    }
}
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidPackageItems implements Rule
{
    private bool $isSeaManifest;

    public function __construct(bool $isSeaManifest)
    {
        $this->isSeaManifest = $isSeaManifest;
    }

    public function passes($attribute, $value)
    {
        // Only validate items for sea manifests
        if (!$this->isSeaManifest) {
            return true;
        }

        // Must be an array
        if (!is_array($value)) {
            return false;
        }

        // Must have at least one item
        if (empty($value)) {
            return false;
        }

        // Check each item has required fields
        foreach ($value as $item) {
            if (!is_array($item)) {
                return false;
            }

            // Description is required and must not be empty
            if (!isset($item['description']) || empty(trim($item['description']))) {
                return false;
            }

            // Quantity is required and must be positive integer
            if (!isset($item['quantity']) || !is_numeric($item['quantity']) || (int)$item['quantity'] < 1) {
                return false;
            }

            // Weight per item is optional but if provided must be non-negative
            if (isset($item['weight_per_item']) && $item['weight_per_item'] !== '' && $item['weight_per_item'] !== null) {
                if (!is_numeric($item['weight_per_item']) || (float)$item['weight_per_item'] < 0) {
                    return false;
                }
            }
        }

        return true;
    }

    public function message()
    {
        return 'Sea packages must have at least one item with a valid description and positive quantity.';
    }
}
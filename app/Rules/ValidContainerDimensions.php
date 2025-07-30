<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidContainerDimensions implements Rule
{
    private bool $isSeaManifest;
    private string $field;

    public function __construct(bool $isSeaManifest, string $field)
    {
        $this->isSeaManifest = $isSeaManifest;
        $this->field = $field;
    }

    public function passes($attribute, $value)
    {
        // Only validate dimensions for sea manifests
        if (!$this->isSeaManifest) {
            return true;
        }

        // Check if value is numeric and positive
        if (!is_numeric($value)) {
            return false;
        }

        $numericValue = (float) $value;

        // Must be greater than 0 and reasonable maximum (1000 inches = ~83 feet)
        return $numericValue > 0 && $numericValue <= 1000;
    }

    public function message()
    {
        $fieldLabels = [
            'length_inches' => 'length',
            'width_inches' => 'width', 
            'height_inches' => 'height'
        ];

        $fieldLabel = $fieldLabels[$this->field] ?? $this->field;
        
        return "The {$fieldLabel} must be a positive number between 0.1 and 1000 inches for sea packages.";
    }
}
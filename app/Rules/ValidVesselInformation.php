<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidVesselInformation implements Rule
{
    private string $manifestType;
    private string $field;

    public function __construct(string $manifestType, string $field)
    {
        $this->manifestType = $manifestType;
        $this->field = $field;
    }

    public function passes($attribute, $value)
    {
        // Only validate vessel information for sea manifests
        if ($this->manifestType !== 'sea') {
            return true;
        }

        // Required vessel fields for sea manifests
        $requiredFields = ['vessel_name', 'voyage_number', 'departure_port'];
        
        if (in_array($this->field, $requiredFields)) {
            return !empty($value) && is_string($value) && strlen(trim($value)) > 0;
        }

        // Optional fields (arrival_port, estimated_arrival_date) can be empty
        return true;
    }

    public function message()
    {
        $fieldLabels = [
            'vessel_name' => 'vessel name',
            'voyage_number' => 'voyage number',
            'departure_port' => 'departure port',
            'arrival_port' => 'arrival port',
            'estimated_arrival_date' => 'estimated arrival date'
        ];

        $fieldLabel = $fieldLabels[$this->field] ?? $this->field;
        
        return "The {$fieldLabel} is required for sea manifests.";
    }
}
<?php

namespace App\Exceptions;

use Exception;

class AirRateNotFoundException extends Exception
{
    protected $weight;

    public function __construct(float $weight, string $message = null)
    {
        $this->weight = $weight;
        
        $message = $message ?: "No air shipping rate found for {$weight} lbs. Please contact administrator to configure appropriate rates.";
        
        parent::__construct($message);
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return "Unable to calculate shipping cost for {$this->weight} lbs. Please contact support for assistance.";
    }
}
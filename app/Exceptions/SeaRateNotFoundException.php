<?php

namespace App\Exceptions;

use Exception;

class SeaRateNotFoundException extends Exception
{
    protected $cubicFeet;

    public function __construct(float $cubicFeet, string $message = null)
    {
        $this->cubicFeet = $cubicFeet;
        
        $message = $message ?: "No sea shipping rate found for {$cubicFeet} cubic feet. Please contact administrator to configure appropriate rates.";
        
        parent::__construct($message);
    }

    public function getCubicFeet(): float
    {
        return $this->cubicFeet;
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return "Unable to calculate shipping cost for {$this->cubicFeet} cubic feet. Please contact support for assistance.";
    }
}
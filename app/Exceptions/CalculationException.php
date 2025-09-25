<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class CalculationException extends Exception
{
    protected $calculationType;
    protected $inputData;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        string $calculationType = '',
        array $inputData = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->calculationType = $calculationType;
        $this->inputData = $inputData;
    }

    public function getCalculationType(): string
    {
        return $this->calculationType;
    }

    public function getInputData(): array
    {
        return $this->inputData;
    }
}
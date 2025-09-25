<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class DataValidationException extends Exception
{
    protected $validationErrors;
    protected $dataType;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $validationErrors = [],
        string $dataType = ''
    ) {
        parent::__construct($message, $code, $previous);
        $this->validationErrors = $validationErrors;
        $this->dataType = $dataType;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }
}
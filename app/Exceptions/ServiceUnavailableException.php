<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ServiceUnavailableException extends Exception
{
    protected $serviceName;
    protected $retryAfter;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        string $serviceName = '',
        ?int $retryAfter = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->serviceName = $serviceName;
        $this->retryAfter = $retryAfter;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
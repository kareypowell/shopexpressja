<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ManifestSummaryException extends Exception
{
    protected $manifestId;
    protected $context;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?int $manifestId = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->manifestId = $manifestId;
        $this->context = $context;
    }

    public function getManifestId(): ?int
    {
        return $this->manifestId;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
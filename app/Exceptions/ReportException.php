<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ReportException extends Exception
{
    protected $reportType;
    protected $context;

    public function __construct(
        string $message = '',
        string $reportType = '',
        array $context = [],
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->reportType = $reportType;
        $this->context = $context;
        
        $fullMessage = $reportType ? "Report Error [{$reportType}]: {$message}" : "Report Error: {$message}";
        parent::__construct($fullMessage, $code, $previous);
    }

    public function getReportType(): string
    {
        return $this->reportType;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getUserFriendlyMessage(): string
    {
        switch ($this->getCode()) {
            case 1001:
                return 'The report data is temporarily unavailable. Please try again in a few moments.';
            case 1002:
                return 'The selected date range contains too much data. Please select a smaller date range.';
            case 1003:
                return 'You do not have permission to access this report.';
            case 1004:
                return 'The report export failed. Please try again or contact support.';
            case 1005:
                return 'The report filters contain invalid values. Please check your selections.';
            default:
                return 'An error occurred while generating the report. Please try again.';
        }
    }
}
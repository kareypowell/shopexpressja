<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportException extends Exception
{
    protected $reportType;
    protected $context;

    public function __construct(string $message = "", string $reportType = null, array $context = [], int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->reportType = $reportType;
        $this->context = $context;
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        \Log::error('Report Exception: ' . $this->getMessage(), [
            'report_type' => $this->reportType,
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ]);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Report generation failed',
                'message' => $this->getMessage(),
                'report_type' => $this->reportType
            ], 500);
        }

        return response()->view('livewire.reports.report-dashboard-error', [
            'error' => $this->getMessage(),
            'reportType' => $this->reportType,
            'context' => $this->context
        ], 500);
    }

    public function getReportType(): ?string
    {
        return $this->reportType;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
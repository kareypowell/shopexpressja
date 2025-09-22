<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ExportException extends Exception
{
    protected $exportType;
    protected $context;

    public function __construct(string $exportType, string $message, array $context = [], int $code = 0, Throwable $previous = null)
    {
        $this->exportType = $exportType;
        $this->context = $context;
        
        $fullMessage = "Export to {$exportType} failed: {$message}";
        
        parent::__construct($fullMessage, $code, $previous);
    }

    /**
     * Get the export type that failed
     */
    public function getExportType(): string
    {
        return $this->exportType;
    }

    /**
     * Get additional context about the failure
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        \Log::error('Report export failed', [
            'export_type' => $this->exportType,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Export failed',
                'message' => $this->getMessage(),
                'export_type' => $this->exportType
            ], 500);
        }

        return redirect()->back()
            ->with('error', "Failed to export report to {$this->exportType}: {$this->getMessage()}");
    }
}
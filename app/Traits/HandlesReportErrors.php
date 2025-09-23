<?php

namespace App\Traits;

use App\Exceptions\ReportException;
use App\Services\ReportErrorHandlingService;
use App\Services\ReportMonitoringService;
use Illuminate\Support\Facades\Auth;
use Throwable;

trait HandlesReportErrors
{
    protected $errorHandlingService;
    protected $monitoringService;

    /**
     * Initialize error handling services
     */
    protected function initializeErrorHandling(): void
    {
        $this->errorHandlingService = app(ReportErrorHandlingService::class);
        $this->monitoringService = app(ReportMonitoringService::class);
    }

    /**
     * Execute report operation with comprehensive error handling
     */
    protected function executeWithErrorHandling(
        callable $operation,
        string $reportType,
        array $context = []
    ): array {
        if (!$this->errorHandlingService) {
            $this->initializeErrorHandling();
        }

        $startTime = microtime(true);
        $userId = Auth::id();

        try {
            // Execute the operation
            $result = $operation();
            
            // Record successful performance metric
            $responseTime = (microtime(true) - $startTime);
            $this->monitoringService->recordPerformanceMetric($reportType, $responseTime, $context);
            
            // Store fallback data for future errors
            if (is_array($result) && !empty($result)) {
                $this->errorHandlingService->storeFallbackData($reportType, $context, $result);
            }
            
            return [
                'success' => true,
                'data' => $result,
                'response_time' => $responseTime
            ];
            
        } catch (Throwable $exception) {
            // Handle the error
            return $this->errorHandlingService->handleReportError(
                $exception,
                $reportType,
                $context,
                $userId
            );
        }
    }

    /**
     * Validate report filters and throw appropriate exceptions
     */
    protected function validateReportFilters(array $filters, string $reportType): void
    {
        // Date range validation
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $startDate = \Carbon\Carbon::parse($filters['start_date']);
            $endDate = \Carbon\Carbon::parse($filters['end_date']);
            
            if ($startDate->gt($endDate)) {
                throw new ReportException(
                    'Start date cannot be after end date',
                    $reportType,
                    $filters,
                    1005
                );
            }
            
            // Check for excessive date range (more than 2 years)
            if ($startDate->diffInDays($endDate) > 730) {
                throw new ReportException(
                    'Date range is too large (maximum 2 years)',
                    $reportType,
                    $filters,
                    1002
                );
            }
        }
        
        // Validate numeric filters
        $numericFilters = ['user_id', 'office_id', 'manifest_id'];
        foreach ($numericFilters as $filter) {
            if (isset($filters[$filter]) && !is_numeric($filters[$filter])) {
                throw new ReportException(
                    "Invalid {$filter}: must be numeric",
                    $reportType,
                    $filters,
                    1005
                );
            }
        }
        
        // Validate enum values
        if (isset($filters['manifest_type']) && 
            !in_array($filters['manifest_type'], ['air', 'sea', 'all'])) {
            throw new ReportException(
                'Invalid manifest type: must be air, sea, or all',
                $reportType,
                $filters,
                1005
            );
        }
    }

    /**
     * Check user permissions for report access
     */
    protected function checkReportPermissions(string $reportType, array $context = []): void
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new ReportException(
                'Authentication required',
                $reportType,
                $context,
                1003
            );
        }
        
        // Check specific report permissions
        $permissionMap = [
            'sales' => 'viewSalesReports',
            'manifest' => 'viewManifestReports',
            'customer' => 'viewCustomerReports',
            'financial' => 'viewFinancialReports'
        ];
        
        if (isset($permissionMap[$reportType])) {
            $permission = $permissionMap[$reportType];
            
            if (!$user->can($permission)) {
                throw new ReportException(
                    "Insufficient permissions for {$reportType} reports",
                    $reportType,
                    $context,
                    1003
                );
            }
        }
        
        // Additional context-based permission checks
        if (isset($context['user_id']) && $context['user_id'] !== $user->id) {
            if (!$user->can('viewAllCustomerData')) {
                throw new ReportException(
                    'Cannot access other customer data',
                    $reportType,
                    $context,
                    1003
                );
            }
        }
    }

    /**
     * Handle memory and performance limits
     */
    protected function checkResourceLimits(array $filters, string $reportType): void
    {
        $memoryLimit = ini_get('memory_limit');
        $currentMemory = memory_get_usage(true);
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        
        // Check if we're approaching memory limit
        if ($currentMemory > ($memoryLimitBytes * 0.8)) {
            throw new ReportException(
                'System memory limit approaching',
                $reportType,
                $filters,
                1002
            );
        }
        
        // Estimate data size based on filters
        $estimatedRecords = $this->estimateRecordCount($filters, $reportType);
        
        if ($estimatedRecords > 50000) {
            throw new ReportException(
                'Dataset too large for processing',
                $reportType,
                $filters,
                1002
            );
        }
    }

    /**
     * Convert memory limit string to bytes
     */
    protected function convertToBytes(string $memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    /**
     * Estimate record count based on filters
     */
    protected function estimateRecordCount(array $filters, string $reportType): int
    {
        // This is a simplified estimation - in production you might want more sophisticated logic
        $baseCount = 1000; // Base estimate
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $days = \Carbon\Carbon::parse($filters['start_date'])
                ->diffInDays(\Carbon\Carbon::parse($filters['end_date']));
            $baseCount *= max(1, $days / 30); // Rough scaling by months
        }
        
        return (int) $baseCount;
    }

    /**
     * Create user-friendly error response for Livewire components
     */
    protected function createErrorResponse(string $message, array $context = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'show_retry' => true,
            'show_support' => false,
            'context' => $context
        ];
    }

    /**
     * Log report access for audit purposes
     */
    protected function logReportAccess(string $reportType, array $filters, bool $success = true): void
    {
        $logData = [
            'user_id' => Auth::id(),
            'report_type' => $reportType,
            'filters' => $filters,
            'success' => $success,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ];
        
        \Log::channel('audit')->info('Report Access', $logData);
    }
}
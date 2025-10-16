<?php

namespace App\Services;

use App\Exceptions\ReportException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;
use Throwable;

class ReportErrorHandlingService
{
    const ERROR_CACHE_PREFIX = 'report_error_';
    const ERROR_RATE_LIMIT_PREFIX = 'report_error_rate_';
    const MAX_ERRORS_PER_HOUR = 10;

    /**
     * Handle report generation errors with logging and user-friendly messages
     */
    public function handleReportError(
        Throwable $exception,
        string $reportType,
        array $context = [],
        ?int $userId = null
    ): array {
        $errorId = $this->generateErrorId();
        
        // Log the error with full context
        $this->logError($exception, $reportType, $context, $errorId, $userId);
        
        // Track error rates
        $this->trackErrorRate($reportType, $userId);
        
        // Check if we should show cached data
        $fallbackData = $this->getFallbackData($reportType, $context);
        
        // Determine user-friendly message
        $userMessage = $this->getUserFriendlyMessage($exception, $reportType);
        
        return [
            'success' => false,
            'error_id' => $errorId,
            'message' => $userMessage,
            'fallback_data' => $fallbackData,
            'retry_suggested' => $this->shouldSuggestRetry($exception),
            'contact_support' => $this->shouldContactSupport($exception, $reportType, $userId)
        ];
    }

    /**
     * Log error with comprehensive context
     */
    protected function logError(
        Throwable $exception,
        string $reportType,
        array $context,
        string $errorId,
        ?int $userId
    ): void {
        $logContext = [
            'error_id' => $errorId,
            'report_type' => $reportType,
            'user_id' => $userId,
            'context' => $context,
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'timestamp' => now()->toISOString()
        ];

        Log::error("Report Generation Error: {$exception->getMessage()}", $logContext);
        
        // Also log to a dedicated report errors log
        Log::channel('reports')->error("Report Error [{$reportType}]: {$exception->getMessage()}", $logContext);
    }

    /**
     * Track error rates for monitoring
     */
    protected function trackErrorRate(string $reportType, ?int $userId): void
    {
        try {
            $hourKey = now()->format('Y-m-d-H');
            
            // Track global error rate
            $globalKey = self::ERROR_RATE_LIMIT_PREFIX . "global_{$reportType}_{$hourKey}";
            Cache::increment($globalKey, 1);
            Cache::put($globalKey, Cache::get($globalKey, 0), 3600);
            
            // Track user-specific error rate if user is provided
            if ($userId) {
                $userKey = self::ERROR_RATE_LIMIT_PREFIX . "user_{$userId}_{$reportType}_{$hourKey}";
                Cache::increment($userKey, 1);
                Cache::put($userKey, Cache::get($userKey, 0), 3600);
            }
        } catch (\Exception $e) {
            // If cache fails, log the error tracking failure but don't throw
            Log::warning("Failed to track error rate for report", [
                'report_type' => $reportType,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get fallback data from cache if available
     */
    protected function getFallbackData(string $reportType, array $context): ?array
    {
        try {
            $cacheKey = "report_fallback_{$reportType}_" . md5(serialize($context));
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            // If cache fails, return null - no fallback data available
            Log::warning("Failed to retrieve fallback data for report", [
                'report_type' => $reportType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Store fallback data for future error scenarios
     */
    public function storeFallbackData(string $reportType, array $context, array $data): void
    {
        try {
            $cacheKey = "report_fallback_{$reportType}_" . md5(serialize($context));
            Cache::put($cacheKey, $data, 3600); // Store for 1 hour
        } catch (\Exception $e) {
            // If cache fails, log but don't throw - this is not critical
            Log::warning("Failed to store fallback data for report", [
                'report_type' => $reportType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user-friendly error message
     */
    protected function getUserFriendlyMessage(Throwable $exception, string $reportType): string
    {
        if ($exception instanceof ReportException) {
            return $exception->getUserFriendlyMessage();
        }

        // Handle common exception types
        if (str_contains($exception->getMessage(), 'timeout')) {
            return 'The report is taking longer than expected to generate. Please try again with a smaller date range.';
        }

        if (str_contains($exception->getMessage(), 'memory')) {
            return 'The report contains too much data to process. Please try filtering to a smaller dataset.';
        }

        if (str_contains($exception->getMessage(), 'permission')) {
            return 'You do not have permission to access this report data.';
        }

        return "Unable to generate the {$reportType} report at this time. Please try again in a few moments.";
    }

    /**
     * Determine if retry should be suggested
     */
    protected function shouldSuggestRetry(Throwable $exception): bool
    {
        // Don't suggest retry for permission errors or validation errors
        if ($exception instanceof ReportException) {
            return !in_array($exception->getCode(), [1003, 1005]);
        }

        // Suggest retry for temporary issues
        return str_contains($exception->getMessage(), 'timeout') ||
               str_contains($exception->getMessage(), 'connection') ||
               str_contains($exception->getMessage(), 'temporary');
    }

    /**
     * Determine if user should contact support
     */
    protected function shouldContactSupport(Throwable $exception, string $reportType, ?int $userId): bool
    {
        // Check if user has hit error rate limit
        if ($userId && $this->hasExceededErrorRate($reportType, $userId)) {
            return true;
        }

        // Contact support for critical errors
        return $exception instanceof ReportException && $exception->getCode() >= 1004;
    }

    /**
     * Check if user has exceeded error rate limit
     */
    protected function hasExceededErrorRate(string $reportType, int $userId): bool
    {
        $hourKey = now()->format('Y-m-d-H');
        $userKey = self::ERROR_RATE_LIMIT_PREFIX . "user_{$userId}_{$reportType}_{$hourKey}";
        
        return Cache::get($userKey, 0) >= self::MAX_ERRORS_PER_HOUR;
    }

    /**
     * Generate unique error ID for tracking
     */
    protected function generateErrorId(): string
    {
        return 'RPT_' . strtoupper(uniqid());
    }

    /**
     * Get error statistics for monitoring
     */
    public function getErrorStatistics(string $reportType, int $hours = 24): array
    {
        $stats = [
            'total_errors' => 0,
            'hourly_breakdown' => [],
            'error_rate' => 0
        ];

        for ($i = 0; $i < $hours; $i++) {
            $hourKey = now()->subHours($i)->format('Y-m-d-H');
            $globalKey = self::ERROR_RATE_LIMIT_PREFIX . "global_{$reportType}_{$hourKey}";
            $hourlyErrors = Cache::get($globalKey, 0);
            
            $stats['total_errors'] += $hourlyErrors;
            $stats['hourly_breakdown'][$hourKey] = $hourlyErrors;
        }

        $stats['error_rate'] = $hours > 0 ? $stats['total_errors'] / $hours : 0;

        return $stats;
    }

    /**
     * Clear error tracking data (for maintenance)
     */
    public function clearErrorTracking(string $reportType = null): void
    {
        $pattern = $reportType 
            ? self::ERROR_RATE_LIMIT_PREFIX . "*{$reportType}*"
            : self::ERROR_RATE_LIMIT_PREFIX . "*";
            
        // Note: This is a simplified implementation
        // In production, you might want to use Redis SCAN for better performance
        Cache::flush();
    }
}
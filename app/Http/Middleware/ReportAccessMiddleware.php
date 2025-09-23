<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\ReportErrorHandlingService;
use App\Exceptions\ReportException;

class ReportAccessMiddleware
{
    const RATE_LIMIT_PREFIX = 'report_rate_limit_';
    const MAX_REQUESTS_PER_MINUTE = 10;
    const MAX_REQUESTS_PER_HOUR = 100;

    protected ReportErrorHandlingService $errorHandlingService;

    public function __construct(ReportErrorHandlingService $errorHandlingService)
    {
        $this->errorHandlingService = $errorHandlingService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            // Check rate limits
            $this->checkRateLimit($user->id, $request);
            
            // Log access attempt
            $this->logAccess($user, $request);
            
            // Check if user has exceeded error rate
            $this->checkErrorRate($user->id);
            
            $response = $next($request);
            
            // Log successful access
            $this->logSuccessfulAccess($user, $request);
            
            return $response;
            
        } catch (ReportException $e) {
            // Handle report-specific exceptions
            Log::channel('reports')->warning('Report access denied', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'route' => $request->route()->getName(),
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);
            
            return response()->json([
                'error' => $e->getUserFriendlyMessage(),
                'error_id' => 'RPT_' . strtoupper(uniqid()),
                'retry_after' => $this->getRetryAfter($e->getCode())
            ], $this->getHttpStatusCode($e->getCode()));
            
        } catch (\Exception $e) {
            // Handle unexpected exceptions
            Log::channel('reports')->error('Unexpected error in report access middleware', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'route' => $request->route()->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'An unexpected error occurred. Please try again later.',
                'error_id' => 'RPT_' . strtoupper(uniqid())
            ], 500);
        }
    }

    /**
     * Check rate limits for the user
     */
    protected function checkRateLimit(int $userId, Request $request): void
    {
        $minuteKey = self::RATE_LIMIT_PREFIX . "minute_{$userId}_" . now()->format('Y-m-d-H-i');
        $hourKey = self::RATE_LIMIT_PREFIX . "hour_{$userId}_" . now()->format('Y-m-d-H');
        
        $minuteCount = Cache::get($minuteKey, 0);
        $hourCount = Cache::get($hourKey, 0);
        
        // Check minute limit
        if ($minuteCount >= self::MAX_REQUESTS_PER_MINUTE) {
            throw new ReportException(
                'Too many report requests per minute',
                'rate_limit',
                ['limit' => self::MAX_REQUESTS_PER_MINUTE, 'period' => 'minute'],
                1006
            );
        }
        
        // Check hour limit
        if ($hourCount >= self::MAX_REQUESTS_PER_HOUR) {
            throw new ReportException(
                'Too many report requests per hour',
                'rate_limit',
                ['limit' => self::MAX_REQUESTS_PER_HOUR, 'period' => 'hour'],
                1007
            );
        }
        
        // Increment counters
        Cache::put($minuteKey, $minuteCount + 1, 60);
        Cache::put($hourKey, $hourCount + 1, 3600);
    }

    /**
     * Check if user has exceeded error rate
     */
    protected function checkErrorRate(int $userId): void
    {
        $hourKey = now()->format('Y-m-d-H');
        $errorKey = ReportErrorHandlingService::ERROR_RATE_LIMIT_PREFIX . "user_{$userId}_*_{$hourKey}";
        
        // This is a simplified check - in production you might want to use Redis SCAN
        // For now, we'll check if the user has been flagged for high error rate
        $errorFlagKey = "user_error_flag_{$userId}";
        
        if (Cache::get($errorFlagKey)) {
            throw new ReportException(
                'Too many recent errors. Please contact support.',
                'error_rate',
                ['user_id' => $userId],
                1008
            );
        }
    }

    /**
     * Log access attempt
     */
    protected function logAccess($user, Request $request): void
    {
        Log::channel('audit')->info('Report access attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route()->getName(),
            'method' => $request->method(),
            'parameters' => $request->all(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log successful access
     */
    protected function logSuccessfulAccess($user, Request $request): void
    {
        Log::channel('audit')->info('Report access successful', [
            'user_id' => $user->id,
            'route' => $request->route()->getName(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get retry after seconds based on error code
     */
    protected function getRetryAfter(int $errorCode): ?int
    {
        switch ($errorCode) {
            case 1006: // Rate limit per minute
                return 60;
            case 1007: // Rate limit per hour
                return 3600;
            case 1008: // Error rate limit
                return 1800; // 30 minutes
            default:
                return null;
        }
    }

    /**
     * Get HTTP status code based on error code
     */
    protected function getHttpStatusCode(int $errorCode): int
    {
        switch ($errorCode) {
            case 1003: // Permission denied
                return 403;
            case 1006: // Rate limit per minute
            case 1007: // Rate limit per hour
            case 1008: // Error rate limit
                return 429;
            case 1005: // Validation error
                return 400;
            default:
                return 500;
        }
    }
}
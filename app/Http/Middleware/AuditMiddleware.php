<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditMiddleware
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
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
        $startTime = microtime(true);
        
        // Skip audit logging for certain routes to avoid noise
        if ($this->shouldSkipAudit($request)) {
            return $next($request);
        }

        // Log the incoming request
        $this->logRequest($request);

        $response = $next($request);

        // Log the response
        $this->logResponse($request, $response, $startTime);

        return $response;
    }

    /**
     * Log incoming HTTP request
     */
    protected function logRequest(Request $request): void
    {
        try {
            $this->auditService->log([
                'user_id' => Auth::id(),
                'event_type' => 'http_request',
                'action' => 'request_received',
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'method' => $request->method(),
                    'route_name' => $request->route()?->getName(),
                    'controller_action' => $request->route()?->getActionName(),
                    'parameters' => $this->filterSensitiveData($request->all()),
                    'headers' => $this->filterSensitiveHeaders($request->headers->all()),
                    'timestamp' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log HTTP request', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl()
            ]);
        }
    }

    /**
     * Log HTTP response
     */
    protected function logResponse(Request $request, $response, float $startTime): void
    {
        try {
            $duration = round((microtime(true) - $startTime) * 1000, 2); // milliseconds

            $this->auditService->log([
                'user_id' => Auth::id(),
                'event_type' => 'http_response',
                'action' => 'response_sent',
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'method' => $request->method(),
                    'status_code' => $response->getStatusCode(),
                    'response_time_ms' => $duration,
                    'route_name' => $request->route()?->getName(),
                    'controller_action' => $request->route()?->getActionName(),
                    'timestamp' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log HTTP response', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl()
            ]);
        }
    }

    /**
     * Determine if audit logging should be skipped for this request
     */
    protected function shouldSkipAudit(Request $request): bool
    {
        $skipRoutes = [
            'livewire.message',
            'livewire.upload-file',
            'livewire.preview-file',
            'horizon.*',
            'telescope.*',
            '_debugbar.*',
        ];

        $skipPaths = [
            '/livewire/',
            '/horizon/',
            '/telescope/',
            '/_debugbar/',
            '/css/',
            '/js/',
            '/img/',
            '/favicon.ico',
        ];

        // Skip based on route name
        $routeName = $request->route()?->getName();
        if ($routeName) {
            foreach ($skipRoutes as $pattern) {
                if (fnmatch($pattern, $routeName)) {
                    return true;
                }
            }
        }

        // Skip based on path
        $path = $request->path();
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, ltrim($skipPath, '/'))) {
                return true;
            }
        }

        // Skip AJAX requests for certain actions
        if ($request->ajax() && in_array($request->method(), ['GET']) && 
            !in_array($routeName, ['admin.audit-logs', 'admin.users', 'admin.packages'])) {
            return true;
        }

        return false;
    }

    /**
     * Filter sensitive data from request parameters
     */
    protected function filterSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_token',
            'remember_token',
            '_token',
            'credit_card',
            'cvv',
            'ssn',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }

        return $data;
    }

    /**
     * Filter sensitive headers
     */
    protected function filterSensitiveHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
        ];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[FILTERED]'];
            }
        }

        return $headers;
    }
}
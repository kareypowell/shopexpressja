<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitoringService;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SecurityMonitoringMiddleware
{
    protected SecurityMonitoringService $securityService;
    protected AuditService $auditService;

    public function __construct(SecurityMonitoringService $securityService, AuditService $auditService)
    {
        $this->securityService = $securityService;
        $this->auditService = $auditService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only monitor authenticated requests
        if (Auth::check()) {
            $this->monitorUserActivity($request);
        }

        // Monitor IP-based activity regardless of authentication
        $this->monitorIPActivity($request);

        return $response;
    }

    /**
     * Monitor user-specific activity patterns
     */
    protected function monitorUserActivity(Request $request): void
    {
        try {
            $user = Auth::user();
            $ipAddress = $request->ip();

            // Analyze user activity for suspicious patterns
            $analysis = $this->securityService->analyzeUserActivity($user, $ipAddress);

            // Generate alerts for high-risk activities
            if ($analysis['risk_score'] >= SecurityMonitoringService::HIGH_RISK) {
                $alertData = array_merge($analysis, [
                    'analysis_type' => 'user_activity',
                    'request_url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $ipAddress
                ]);

                $this->securityService->generateSecurityAlert($alertData);
            }

            // Log suspicious activity patterns
            if ($analysis['risk_score'] >= SecurityMonitoringService::MEDIUM_RISK) {
                $this->auditService->logSecurityEvent('suspicious_activity_detected', [
                    'user_id' => $user->id,
                    'risk_score' => $analysis['risk_score'],
                    'risk_level' => $analysis['risk_level'],
                    'alerts' => $analysis['alerts'],
                    'ip_address' => $ipAddress,
                    'user_agent' => $request->userAgent(),
                    'request_url' => $request->fullUrl()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Security monitoring failed for user activity', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);
        }
    }

    /**
     * Monitor IP-based activity patterns
     */
    protected function monitorIPActivity(Request $request): void
    {
        try {
            $ipAddress = $request->ip();

            // Skip monitoring for local/internal IPs
            if ($this->isInternalIP($ipAddress)) {
                return;
            }

            // Analyze IP activity patterns
            $analysis = $this->securityService->analyzeIPActivity($ipAddress);

            // Generate alerts for high-risk IP activity
            if ($analysis['risk_score'] >= SecurityMonitoringService::HIGH_RISK) {
                $alertData = array_merge($analysis, [
                    'analysis_type' => 'ip_activity',
                    'request_url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'authenticated_user' => Auth::id()
                ]);

                $this->securityService->generateSecurityAlert($alertData);
            }

        } catch (\Exception $e) {
            Log::error('Security monitoring failed for IP activity', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
        }
    }

    /**
     * Check if IP address is internal/local
     */
    protected function isInternalIP(string $ip): bool
    {
        // Skip monitoring for localhost and private IP ranges
        return in_array($ip, ['127.0.0.1', '::1']) || 
               filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
<?php

namespace App\Listeners;

use App\Services\SecurityMonitoringService;
use App\Services\AuditService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;

class SecurityMonitoringListener
{
    /** @var SecurityMonitoringService */
    protected $securityService;
    
    /** @var AuditService */
    protected $auditService;

    public function __construct(SecurityMonitoringService $securityService, AuditService $auditService)
    {
        $this->securityService = $securityService;
        $this->auditService = $auditService;
    }

    /**
     * Handle successful login events
     */
    public function handleLogin(Login $event): void
    {
        try {
            $user = $event->user;
            $ipAddress = request()->ip();

            // Analyze user activity after login
            $analysis = $this->securityService->analyzeUserActivity($user, $ipAddress);

            // Check for suspicious login patterns
            if ($this->isSuspiciousLogin($user, $ipAddress)) {
                $alertData = array_merge($analysis, [
                    'analysis_type' => 'suspicious_login',
                    'event' => 'login',
                    'ip_address' => $ipAddress,
                    'user_agent' => request()->userAgent()
                ]);

                $this->securityService->generateSecurityAlert($alertData);
            }

            // Log successful authentication
            $this->auditService->logAuthenticationEvent('login', $user, [
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent(),
                'guard' => $event->guard,
                'risk_score' => $analysis['risk_score'],
                'remember' => request()->has('remember')
            ]);

        } catch (\Exception $e) {
            Log::error('Security monitoring failed for login event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null
            ]);
        }
    }

    /**
     * Handle logout events
     */
    public function handleLogout(Logout $event): void
    {
        try {
            if ($event->user) {
                $this->auditService->logAuthenticationEvent('logout', $event->user, [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'guard' => $event->guard,
                    'session_duration' => $this->calculateSessionDuration($event->user)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Security monitoring failed for logout event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null
            ]);
        }
    }

    /**
     * Handle failed authentication attempts
     */
    public function handleFailed(Failed $event): void
    {
        try {
            $ipAddress = request()->ip();
            $credentials = $event->credentials;

            // Analyze IP activity for failed attempts
            $analysis = $this->securityService->analyzeIPActivity($ipAddress);

            // Generate alert for high-risk failed attempts
            if ($analysis['risk_score'] >= SecurityMonitoringService::MEDIUM_RISK) {
                $alertData = array_merge($analysis, [
                    'analysis_type' => 'failed_authentication',
                    'event' => 'authentication_failed',
                    'attempted_email' => $credentials['email'] ?? null,
                    'user_agent' => request()->userAgent()
                ]);

                $this->securityService->generateSecurityAlert($alertData);
            }

            // Log failed authentication attempt
            $this->auditService->logSecurityEvent('failed_authentication', [
                'attempted_email' => $credentials['email'] ?? null,
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent(),
                'guard' => $event->guard,
                'risk_score' => $analysis['risk_score'],
                'failure_reason' => 'invalid_credentials'
            ]);

        } catch (\Exception $e) {
            Log::error('Security monitoring failed for failed authentication', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
        }
    }

    /**
     * Handle account lockout events
     */
    public function handleLockout(Lockout $event): void
    {
        try {
            $ipAddress = request()->ip();

            // Generate critical security alert for lockouts
            $alertData = [
                'risk_score' => 100,
                'risk_level' => 'critical',
                'alerts' => ['Account lockout triggered due to excessive failed attempts'],
                'analysis_type' => 'account_lockout',
                'event' => 'account_locked',
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent()
            ];

            $this->securityService->generateSecurityAlert($alertData);

            // Log lockout event
            $this->auditService->logSecurityEvent('account_lockout', [
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent(),
                'lockout_duration' => config('auth.lockout_duration', 900), // 15 minutes default
                'severity' => 'critical'
            ]);

        } catch (\Exception $e) {
            Log::error('Security monitoring failed for lockout event', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
        }
    }

    /**
     * Handle password reset events
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        try {
            $user = $event->user;

            $this->auditService->logAuthenticationEvent('password_reset', $user, [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'reset_method' => 'email_link'
            ]);

        } catch (\Exception $e) {
            Log::error('Security monitoring failed for password reset event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null
            ]);
        }
    }

    /**
     * Check if login appears suspicious
     */
    protected function isSuspiciousLogin($user, string $ipAddress): bool
    {
        // Check for login from new IP address
        $recentIPs = $this->auditService->getUserRecentIPs($user->id, 30); // Last 30 days
        $isNewIP = !in_array($ipAddress, $recentIPs);

        // Check for login outside normal hours (if we have historical data)
        $isOffHours = $this->isOffHoursLogin($user);

        // Check for rapid successive logins
        $hasRapidLogins = $this->hasRapidSuccessiveLogins($user->id);

        return $isNewIP || $isOffHours || $hasRapidLogins;
    }

    /**
     * Check if login is during off-hours for the user
     */
    protected function isOffHoursLogin($user): bool
    {
        $currentHour = now()->hour;
        
        // Consider 11 PM to 6 AM as off-hours
        return $currentHour >= 23 || $currentHour <= 6;
    }

    /**
     * Check for rapid successive logins
     */
    protected function hasRapidSuccessiveLogins(int $userId): bool
    {
        $recentLogins = $this->auditService->getUserRecentLogins($userId, 15); // Last 15 minutes
        
        return count($recentLogins) > 3; // More than 3 logins in 15 minutes
    }

    /**
     * Calculate session duration (approximate)
     */
    protected function calculateSessionDuration($user): ?int
    {
        $lastLogin = $this->auditService->getLastLoginTime($user->id);
        
        if ($lastLogin) {
            return now()->diffInMinutes($lastLogin);
        }

        return null;
    }
}
<?php

namespace App\Listeners;

use App\Services\AuditService;
use App\Services\FailedAuthenticationTracker;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

class AuthenticationAuditListener
{
    protected $auditService;
    protected $failedAuthTracker;

    public function __construct(AuditService $auditService, FailedAuthenticationTracker $failedAuthTracker)
    {
        $this->auditService = $auditService;
        $this->failedAuthTracker = $failedAuthTracker;
    }

    /**
     * Handle user login events
     */
    public function handleLogin(Login $event): void
    {
        try {
            // Track successful authentication (resets failed attempt counters)
            $this->failedAuthTracker->trackSuccessfulAttempt($event->user->email);

            $this->auditService->logAuthentication('login', $event->user, [
                'guard' => $event->guard,
                'remember' => request()->has('remember'),
                'login_method' => 'credentials',
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'user_role' => $event->user->role->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to audit login event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null
            ]);
        }
    }

    /**
     * Handle user logout events
     */
    public function handleLogout(Logout $event): void
    {
        try {
            $this->auditService->logAuthentication('logout', $event->user, [
                'guard' => $event->guard,
                'session_duration' => $this->calculateSessionDuration($event->user),
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'user_role' => $event->user->role->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to audit logout event', [
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
            // Track the failed attempt with comprehensive monitoring
            $this->failedAuthTracker->trackFailedAttempt($event->credentials, $event->guard);
        } catch (\Exception $e) {
            Log::error('Failed to audit failed login event', [
                'error' => $e->getMessage(),
                'credentials' => $this->sanitizeCredentials($event->credentials ?? [])
            ]);
        }
    }

    /**
     * Handle authentication attempts
     */
    public function handleAttempting(Attempting $event): void
    {
        try {
            // Only log if this looks suspicious (multiple attempts from same IP)
            if ($this->isSuspiciousAttempt()) {
                $this->auditService->logSecurityEvent('login_attempt', [
                    'guard' => $event->guard,
                    'credentials' => $this->sanitizeCredentials($event->credentials),
                    'severity' => 'low',
                    'attempted_email' => $event->credentials['email'] ?? null,
                    'remember' => $event->remember,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to audit login attempt event', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle user registration events
     */
    public function handleRegistered(Registered $event): void
    {
        try {
            $this->auditService->logAuthentication('registration', $event->user, [
                'registration_method' => 'web_form',
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'default_role' => $event->user->role->name ?? 'customer',
                'email_verified' => $event->user->hasVerifiedEmail(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to audit registration event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null
            ]);
        }
    }

    /**
     * Handle password reset events
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        try {
            $this->auditService->logAuthentication('password_reset', $event->user, [
                'reset_method' => 'email_link',
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to audit password reset event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null
            ]);
        }
    }

    /**
     * Handle email verification events
     */
    public function handleVerified(Verified $event): void
    {
        try {
            $this->auditService->logAuthentication('email_verified', $event->user, [
                'verification_method' => 'email_link',
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to audit email verification event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id ?? null
            ]);
        }
    }

    /**
     * Calculate session duration for logout events
     */
    protected function calculateSessionDuration($user): ?int
    {
        try {
            // Try to get the last login time from session or cache
            $lastLogin = session('last_login_time');
            if ($lastLogin) {
                return now()->diffInMinutes($lastLogin);
            }
        } catch (\Exception $e) {
            // Ignore errors in session duration calculation
        }

        return null;
    }

    /**
     * Sanitize credentials for logging (remove sensitive data)
     */
    protected function sanitizeCredentials(array $credentials): array
    {
        $sanitized = $credentials;
        
        // Remove password and other sensitive fields
        unset($sanitized['password']);
        unset($sanitized['password_confirmation']);
        unset($sanitized['token']);
        
        return $sanitized;
    }

    /**
     * Check if this is a suspicious login attempt
     */
    protected function isSuspiciousAttempt(): bool
    {
        try {
            $ip = request()->ip();
            $cacheKey = "login_attempts_{$ip}";
            
            // Get current attempt count
            $attempts = cache()->get($cacheKey, 0);
            
            // Increment and store for 15 minutes
            cache()->put($cacheKey, $attempts + 1, now()->addMinutes(15));
            
            // Consider suspicious if more than 3 attempts in 15 minutes
            return $attempts >= 3;
        } catch (\Exception $e) {
            return false;
        }
    }
}
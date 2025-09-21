<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FailedAuthenticationTracker
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Track a failed authentication attempt
     */
    public function trackFailedAttempt(array $credentials, string $guard = 'web'): void
    {
        $ip = request()->ip();
        $email = $credentials['email'] ?? null;
        $userAgent = request()->userAgent();

        // Track by IP address
        $this->incrementFailedAttempts($ip, 'ip');
        
        // Track by email if provided
        if ($email) {
            $this->incrementFailedAttempts($email, 'email');
        }

        // Determine threat level
        $threatLevel = $this->calculateThreatLevel($ip, $email);

        // Log the failed attempt
        $this->auditService->logSecurityEvent('failed_authentication', [
            'severity' => $threatLevel,
            'guard' => $guard,
            'attempted_email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'failure_reason' => 'invalid_credentials',
            'attempts_by_ip' => $this->getFailedAttempts($ip, 'ip'),
            'attempts_by_email' => $email ? $this->getFailedAttempts($email, 'email') : 0,
            'is_suspicious' => $threatLevel === 'high',
        ]);

        // Check if we should trigger additional security measures
        if ($threatLevel === 'high') {
            $this->handleHighThreatAttempt($ip, $email);
        }
    }

    /**
     * Track a successful authentication (to reset counters)
     */
    public function trackSuccessfulAttempt(string $email): void
    {
        $ip = request()->ip();
        
        // Reset failed attempt counters
        $this->resetFailedAttempts($ip, 'ip');
        $this->resetFailedAttempts($email, 'email');

        // Log successful authentication after previous failures
        $this->auditService->logSecurityEvent('authentication_success_after_failures', [
            'severity' => 'low',
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Check if an IP or email is currently blocked
     */
    public function isBlocked(string $identifier, string $type = 'ip'): bool
    {
        $blockKey = "auth_blocked_{$type}_{$identifier}";
        return Cache::has($blockKey);
    }

    /**
     * Get failed attempt statistics
     */
    public function getFailedAttemptStats(int $hours = 24): array
    {
        try {
            $since = now()->subHours($hours);
            
            $stats = AuditLog::where('event_type', 'security_event')
                ->where('action', 'failed_authentication')
                ->where('created_at', '>=', $since)
                ->get();

            $uniqueIps = $stats->pluck('ip_address')->unique()->count();
            $uniqueEmails = $stats->pluck('additional_data.attempted_email')->filter()->unique()->count();
            $highThreatAttempts = $stats->where('additional_data.severity', 'high')->count();

            return [
                'total_attempts' => $stats->count(),
                'unique_ips' => $uniqueIps,
                'unique_emails' => $uniqueEmails,
                'high_threat_attempts' => $highThreatAttempts,
                'attempts_by_hour' => $this->groupAttemptsByHour($stats),
                'top_attacking_ips' => $this->getTopAttackingIps($stats),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get failed attempt stats', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Increment failed attempts counter
     */
    protected function incrementFailedAttempts(string $identifier, string $type): int
    {
        $key = "failed_attempts_{$type}_{$identifier}";
        $attempts = Cache::get($key, 0) + 1;
        
        // Store for 1 hour
        Cache::put($key, $attempts, now()->addHour());
        
        return $attempts;
    }

    /**
     * Get current failed attempts count
     */
    protected function getFailedAttempts(string $identifier, string $type): int
    {
        $key = "failed_attempts_{$type}_{$identifier}";
        return Cache::get($key, 0);
    }

    /**
     * Reset failed attempts counter
     */
    protected function resetFailedAttempts(string $identifier, string $type): void
    {
        $key = "failed_attempts_{$type}_{$identifier}";
        Cache::forget($key);
    }

    /**
     * Calculate threat level based on failed attempts
     */
    protected function calculateThreatLevel(string $ip, ?string $email): string
    {
        $ipAttempts = $this->getFailedAttempts($ip, 'ip');
        $emailAttempts = $email ? $this->getFailedAttempts($email, 'email') : 0;

        // High threat: 10+ attempts from IP or 5+ attempts on same email
        if ($ipAttempts >= 10 || $emailAttempts >= 5) {
            return 'high';
        }

        // Medium threat: 5+ attempts from IP or 3+ attempts on same email
        if ($ipAttempts >= 5 || $emailAttempts >= 3) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Handle high threat authentication attempts
     */
    protected function handleHighThreatAttempt(string $ip, ?string $email): void
    {
        // Block IP for 30 minutes
        $ipBlockKey = "auth_blocked_ip_{$ip}";
        Cache::put($ipBlockKey, true, now()->addMinutes(30));

        // Block email for 15 minutes if provided
        if ($email) {
            $emailBlockKey = "auth_blocked_email_{$email}";
            Cache::put($emailBlockKey, true, now()->addMinutes(15));
        }

        // Log the blocking action
        $this->auditService->logSecurityEvent('authentication_blocked', [
            'severity' => 'critical',
            'ip_address' => $ip,
            'blocked_email' => $email,
            'block_duration_minutes' => 30,
            'reason' => 'excessive_failed_attempts',
        ]);

        // TODO: Send notification to security administrators
        // This could be implemented as a notification or email alert
    }

    /**
     * Group attempts by hour for statistics
     */
    protected function groupAttemptsByHour($attempts): array
    {
        return $attempts->groupBy(function ($attempt) {
            return $attempt->created_at->format('Y-m-d H:00');
        })->map(function ($group) {
            return $group->count();
        })->toArray();
    }

    /**
     * Get top attacking IP addresses
     */
    protected function getTopAttackingIps($attempts, int $limit = 10): array
    {
        return $attempts->groupBy('ip_address')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take($limit)
            ->toArray();
    }
}
<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class SecurityMonitoringService
{
    /**
     * Risk score thresholds
     */
    const LOW_RISK = 25;
    const MEDIUM_RISK = 50;
    const HIGH_RISK = 75;
    const CRITICAL_RISK = 90;

    /**
     * Time windows for pattern analysis (in minutes)
     */
    const SHORT_WINDOW = 15;
    const MEDIUM_WINDOW = 60;
    const LONG_WINDOW = 1440; // 24 hours

    /**
     * Analyze user activity for suspicious patterns
     */
    public function analyzeUserActivity(User $user, string $ipAddress = null): array
    {
        $riskScore = 0;
        $alerts = [];
        $timeWindow = now()->subMinutes(self::MEDIUM_WINDOW);

        // Check for multiple failed login attempts
        $failedLogins = $this->getFailedLoginAttempts($user->id, $ipAddress, $timeWindow);
        if ($failedLogins >= 5) {
            $riskScore += 30;
            $alerts[] = "Multiple failed login attempts detected ({$failedLogins} attempts)";
        }

        // Check for unusual activity volume
        $activityCount = $this->getUserActivityCount($user->id, $timeWindow);
        $averageActivity = $this->getUserAverageActivity($user->id);
        
        if ($activityCount > ($averageActivity * 3)) {
            $riskScore += 25;
            $alerts[] = "Unusually high activity volume detected";
        }

        // Check for access from multiple IP addresses
        $uniqueIPs = $this->getUniqueIPAddresses($user->id, $timeWindow);
        if ($uniqueIPs > 3) {
            $riskScore += 20;
            $alerts[] = "Access from multiple IP addresses detected";
        }

        // Check for bulk operations
        $bulkOperations = $this->getBulkOperations($user->id, $timeWindow);
        if ($bulkOperations > 10) {
            $riskScore += 15;
            $alerts[] = "Multiple bulk operations detected";
        }

        // Check for privilege escalation attempts
        $privilegeAttempts = $this->getPrivilegeEscalationAttempts($user->id, $timeWindow);
        if ($privilegeAttempts > 0) {
            $riskScore += 40;
            $alerts[] = "Privilege escalation attempts detected";
        }

        return [
            'risk_score' => min($riskScore, 100),
            'risk_level' => $this->getRiskLevel($riskScore),
            'alerts' => $alerts,
            'analysis_time' => now(),
            'user_id' => $user->id
        ];
    }

    /**
     * Monitor IP address for suspicious activity
     */
    public function analyzeIPActivity(string $ipAddress): array
    {
        $riskScore = 0;
        $alerts = [];
        $timeWindow = now()->subMinutes(self::MEDIUM_WINDOW);

        // Check for multiple user access from same IP
        $uniqueUsers = $this->getUniqueUsersFromIP($ipAddress, $timeWindow);
        if ($uniqueUsers > 5) {
            $riskScore += 35;
            $alerts[] = "Multiple users accessing from same IP address";
        }

        // Check for rapid-fire requests
        $requestCount = $this->getIPRequestCount($ipAddress, now()->subMinutes(self::SHORT_WINDOW));
        if ($requestCount > 100) {
            $riskScore += 30;
            $alerts[] = "High frequency requests detected";
        }

        // Check for failed authentication attempts
        $failedAttempts = $this->getFailedLoginAttempts(null, $ipAddress, $timeWindow);
        if ($failedAttempts > 10) {
            $riskScore += 40;
            $alerts[] = "Multiple failed authentication attempts from IP";
        }

        return [
            'risk_score' => min($riskScore, 100),
            'risk_level' => $this->getRiskLevel($riskScore),
            'alerts' => $alerts,
            'analysis_time' => now(),
            'ip_address' => $ipAddress
        ];
    }

    /**
     * Detect suspicious patterns in system-wide activity
     */
    public function detectSystemAnomalies(): array
    {
        $anomalies = [];
        $timeWindow = now()->subMinutes(self::MEDIUM_WINDOW);

        // Check for unusual deletion patterns
        $deletions = AuditLog::where('action', 'delete')
            ->where('created_at', '>=', $timeWindow)
            ->count();
        
        if ($deletions > 20) {
            $anomalies[] = [
                'type' => 'mass_deletion',
                'severity' => 'high',
                'description' => "Unusual number of deletions detected ({$deletions} deletions)",
                'count' => $deletions
            ];
        }

        // Check for bulk data modifications
        $bulkUpdates = AuditLog::where('action', 'update')
            ->where('created_at', '>=', $timeWindow)
            ->whereNotNull('additional_data->bulk_operation')
            ->count();

        if ($bulkUpdates > 5) {
            $anomalies[] = [
                'type' => 'bulk_modifications',
                'severity' => 'medium',
                'description' => "Multiple bulk update operations detected",
                'count' => $bulkUpdates
            ];
        }

        // Check for unauthorized access attempts
        $unauthorizedAttempts = AuditLog::where('event_type', 'security_event')
            ->where('action', 'unauthorized_access')
            ->where('created_at', '>=', $timeWindow)
            ->count();

        if ($unauthorizedAttempts > 0) {
            $anomalies[] = [
                'type' => 'unauthorized_access',
                'severity' => 'critical',
                'description' => "Unauthorized access attempts detected",
                'count' => $unauthorizedAttempts
            ];
        }

        return $anomalies;
    }

    /**
     * Generate security alert for administrators
     */
    public function generateSecurityAlert(array $alertData): void
    {
        try {
            // Log the security event
            $this->logSecurityEvent($alertData);

            // Send notifications to security administrators
            $this->notifySecurityAdministrators($alertData);

            // Cache the alert for dashboard display
            $this->cacheSecurityAlert($alertData);

        } catch (\Exception $e) {
            Log::error('Failed to generate security alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData
            ]);
        }
    }

    /**
     * Get failed login attempts for user or IP
     */
    private function getFailedLoginAttempts($userId = null, string $ipAddress = null, Carbon $since = null): int
    {
        $query = AuditLog::where('event_type', 'authentication')
            ->where('action', 'failed_login');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query->count();
    }

    /**
     * Get user activity count in time window
     */
    private function getUserActivityCount(int $userId, Carbon $since): int
    {
        return AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * Get user's average daily activity
     */
    private function getUserAverageActivity(int $userId): float
    {
        $cacheKey = "user_avg_activity_{$userId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            $thirtyDaysAgo = now()->subDays(30);
            $totalActivity = AuditLog::where('user_id', $userId)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count();
            
            return $totalActivity / 30;
        });
    }

    /**
     * Get unique IP addresses for user in time window
     */
    private function getUniqueIPAddresses(int $userId, Carbon $since): int
    {
        return AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->distinct('ip_address')
            ->count();
    }

    /**
     * Get bulk operations count for user
     */
    private function getBulkOperations(int $userId, Carbon $since): int
    {
        return AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('additional_data->bulk_operation')
            ->count();
    }

    /**
     * Get privilege escalation attempts
     */
    private function getPrivilegeEscalationAttempts(int $userId, Carbon $since): int
    {
        return AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('event_type', 'security_event')
            ->where('action', 'unauthorized_access')
            ->count();
    }

    /**
     * Get unique users from IP address
     */
    private function getUniqueUsersFromIP(string $ipAddress, Carbon $since): int
    {
        return AuditLog::where('ip_address', $ipAddress)
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count();
    }

    /**
     * Get request count from IP address
     */
    private function getIPRequestCount(string $ipAddress, Carbon $since): int
    {
        return AuditLog::where('ip_address', $ipAddress)
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * Determine risk level from score
     */
    private function getRiskLevel(int $score): string
    {
        if ($score >= self::CRITICAL_RISK) return 'critical';
        if ($score >= self::HIGH_RISK) return 'high';
        if ($score >= self::MEDIUM_RISK) return 'medium';
        if ($score >= self::LOW_RISK) return 'low';
        return 'minimal';
    }

    /**
     * Log security event to audit log
     */
    private function logSecurityEvent(array $alertData): void
    {
        AuditLog::create([
            'user_id' => $alertData['user_id'] ?? null,
            'event_type' => 'security_event',
            'action' => 'security_alert_generated',
            'ip_address' => $alertData['ip_address'] ?? null,
            'additional_data' => [
                'risk_score' => $alertData['risk_score'],
                'risk_level' => $alertData['risk_level'],
                'alerts' => $alertData['alerts'],
                'analysis_type' => $alertData['analysis_type'] ?? 'user_activity'
            ]
        ]);
    }

    /**
     * Notify security administrators
     */
    private function notifySecurityAdministrators(array $alertData): void
    {
        $securityAdmins = User::whereHas('role', function ($query) {
            $query->where('name', 'superadmin');
        })->get();

        foreach ($securityAdmins as $admin) {
            $admin->notify(new SecurityAlertNotification($alertData));
        }
    }

    /**
     * Cache security alert for dashboard
     */
    private function cacheSecurityAlert(array $alertData): void
    {
        $cacheKey = 'security_alerts_' . now()->format('Y-m-d');
        $alerts = Cache::get($cacheKey, []);
        
        $alerts[] = array_merge($alertData, [
            'timestamp' => now()->toISOString()
        ]);

        // Keep only last 100 alerts per day
        if (count($alerts) > 100) {
            $alerts = array_slice($alerts, -100);
        }

        Cache::put($cacheKey, $alerts, 86400); // Cache for 24 hours
    }
}
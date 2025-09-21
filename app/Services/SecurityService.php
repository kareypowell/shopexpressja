<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SecurityService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Calculate risk level based on security events
     */
    public function calculateRiskLevel(array $securityData): string
    {
        $riskScore = 0;
        
        // Failed login attempts (high risk)
        $failedLogins = $securityData['failed_logins'] ?? 0;
        if ($failedLogins > 10) {
            $riskScore += 30;
        } elseif ($failedLogins > 5) {
            $riskScore += 20;
        } elseif ($failedLogins > 0) {
            $riskScore += 10;
        }

        // Suspicious activities (very high risk)
        $suspiciousActivities = $securityData['suspicious_activities'] ?? 0;
        if ($suspiciousActivities > 5) {
            $riskScore += 40;
        } elseif ($suspiciousActivities > 2) {
            $riskScore += 30;
        } elseif ($suspiciousActivities > 0) {
            $riskScore += 20;
        }

        // Security alerts (medium to high risk)
        $securityAlerts = $securityData['security_alerts'] ?? 0;
        if ($securityAlerts > 3) {
            $riskScore += 25;
        } elseif ($securityAlerts > 1) {
            $riskScore += 15;
        } elseif ($securityAlerts > 0) {
            $riskScore += 10;
        }

        // Unique IPs (potential indicator of distributed attacks)
        $uniqueIps = $securityData['unique_ips'] ?? 0;
        if ($uniqueIps > 20) {
            $riskScore += 15;
        } elseif ($uniqueIps > 10) {
            $riskScore += 10;
        } elseif ($uniqueIps > 5) {
            $riskScore += 5;
        }

        // Determine risk level based on score
        if ($riskScore >= 70) {
            return 'Critical';
        } elseif ($riskScore >= 50) {
            return 'High';
        } elseif ($riskScore >= 30) {
            return 'Medium';
        } elseif ($riskScore >= 10) {
            return 'Low';
        } else {
            return 'Minimal';
        }
    }

    /**
     * Get security dashboard data with proper risk assessment
     */
    public function getSecurityDashboardData(int $hours = 24): array
    {
        $since = now()->subHours($hours);
        
        // Get security events
        $securityEvents = AuditLog::where('event_type', 'security_event')
            ->where('created_at', '>=', $since)
            ->get();

        // Get authentication events
        $authEvents = AuditLog::where('event_type', 'authentication')
            ->where('created_at', '>=', $since)
            ->get();

        $failedLogins = $authEvents->where('action', 'failed_authentication')->count();
        $suspiciousActivities = $securityEvents->where('action', 'suspicious_activity_detected')->count();
        $securityAlerts = $securityEvents->where('action', 'security_alert_generated')->count();
        $uniqueIps = $securityEvents->pluck('ip_address')->filter()->unique()->count();

        $securityData = [
            'total_events' => $securityEvents->count(),
            'failed_logins' => $failedLogins,
            'suspicious_activities' => $suspiciousActivities,
            'security_alerts' => $securityAlerts,
            'unique_ips' => $uniqueIps,
        ];

        // Calculate risk assessment
        $riskLevel = $this->calculateRiskLevel($securityData);
        $riskScore = $this->calculateRiskScore($securityData);
        $highRiskAlerts = $this->getHighRiskAlerts($securityEvents);

        // Get recent security alerts with proper risk levels
        $recentAlerts = $this->getRecentSecurityAlerts($hours);

        return [
            'security_events' => $securityData['total_events'],
            'failed_logins' => $failedLogins,
            'suspicious_activities' => $suspiciousActivities,
            'unique_ips' => $uniqueIps,
            'risk_assessment' => [
                'level' => $riskLevel,
                'score' => $riskScore,
                'high_risk_alerts' => $highRiskAlerts,
                'trend' => $this->getRiskTrend($hours),
            ],
            'recent_alerts' => $recentAlerts,
        ];
    }

    /**
     * Calculate numerical risk score
     */
    public function calculateRiskScore(array $securityData): int
    {
        $score = 0;
        
        $score += min(($securityData['failed_logins'] ?? 0) * 3, 30);
        $score += min(($securityData['suspicious_activities'] ?? 0) * 8, 40);
        $score += min(($securityData['security_alerts'] ?? 0) * 5, 25);
        $score += min(floor(($securityData['unique_ips'] ?? 0) / 2), 15);
        
        return min($score, 100);
    }

    /**
     * Get high risk alerts count
     */
    public function getHighRiskAlerts(object $securityEvents): int
    {
        return $securityEvents->filter(function ($event) {
            $severity = $event->additional_data['severity'] ?? 'low';
            return in_array($severity, ['high', 'critical']);
        })->count();
    }

    /**
     * Get risk trend (increasing, decreasing, stable)
     */
    public function getRiskTrend(int $hours = 24): string
    {
        $currentPeriod = now()->subHours($hours);
        $previousPeriod = now()->subHours($hours * 2);
        
        $currentEvents = AuditLog::where('event_type', 'security_event')
            ->where('created_at', '>=', $currentPeriod)
            ->count();
            
        $previousEvents = AuditLog::where('event_type', 'security_event')
            ->whereBetween('created_at', [$previousPeriod, $currentPeriod])
            ->count();

        if ($currentEvents > $previousEvents * 1.2) {
            return 'Increasing';
        } elseif ($currentEvents < $previousEvents * 0.8) {
            return 'Decreasing';
        } else {
            return 'Stable';
        }
    }

    /**
     * Get recent security alerts with proper risk levels
     */
    public function getRecentSecurityAlerts(int $hours = 24): array
    {
        $since = now()->subHours($hours);
        
        $alerts = AuditLog::where('event_type', 'security_event')
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $alerts->map(function ($alert) {
            $severity = $alert->additional_data['severity'] ?? null;
            $riskLevel = $this->mapSeverityToRiskLevel($severity);
            
            return [
                'time' => $alert->created_at->format('M j, Y H:i'),
                'risk_level' => $riskLevel,
                'type' => $this->formatEventType($alert->action),
                'description' => $this->getAlertDescription($alert),
                'ip_address' => $alert->ip_address ?? 'Unknown',
                'status' => 'Acknowledged', // Default status
            ];
        })->toArray();
    }

    /**
     * Map severity to risk level
     */
    private function mapSeverityToRiskLevel(?string $severity): string
    {
        switch ($severity) {
            case 'critical':
                return 'Critical';
            case 'high':
                return 'High';
            case 'medium':
                return 'Medium';
            case 'low':
                return 'Low';
            default:
                return 'Medium'; // Default to Medium instead of Unknown
        }
    }

    /**
     * Format event type for display
     */
    private function formatEventType(string $action): string
    {
        switch ($action) {
            case 'failed_authentication':
                return 'Authentication success after failures';
            case 'suspicious_activity_detected':
                return 'Suspicious Activity';
            case 'security_alert_generated':
                return 'Security Alert';
            case 'multiple_failed_logins':
                return 'Multiple Failed Logins';
            case 'unusual_access_pattern':
                return 'Unusual Access Pattern';
            default:
                return ucwords(str_replace('_', ' ', $action));
        }
    }

    /**
     * Get alert description
     */
    private function getAlertDescription(AuditLog $alert): string
    {
        $action = $alert->action;
        $additionalData = $alert->additional_data ?? [];
        
        switch ($action) {
            case 'failed_authentication':
                return 'Security event detected';
            case 'suspicious_activity_detected':
                return 'Suspicious activity pattern identified';
            case 'security_alert_generated':
                return 'Automated security alert triggered';
            case 'multiple_failed_logins':
                return 'Multiple failed login attempts detected';
            case 'unusual_access_pattern':
                return 'Unusual access pattern detected';
            default:
                return 'Security event detected';
        }
    }

    /**
     * Log security event with proper severity
     */
    public function logSecurityEvent(string $action, array $data = [], string $severity = 'medium'): void
    {
        $this->auditService->logSecurityEvent($action, array_merge($data, [
            'severity' => $severity,
            'timestamp' => now()->toISOString(),
            'risk_assessment' => $this->mapSeverityToRiskLevel($severity),
        ]));
    }

    /**
     * Detect and log suspicious activity
     */
    public function detectSuspiciousActivity(int $userId, string $ipAddress): void
    {
        // Check for multiple failed logins from same IP
        $recentFailures = AuditLog::where('event_type', 'authentication')
            ->where('action', 'failed_authentication')
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures >= 5) {
            $this->logSecurityEvent('multiple_failed_logins', [
                'ip_address' => $ipAddress,
                'user_id' => $userId,
                'failure_count' => $recentFailures,
            ], 'high');
        }

        // Check for unusual access patterns
        $recentLogins = AuditLog::where('event_type', 'authentication')
            ->where('action', 'login')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(24))
            ->distinct('ip_address')
            ->count();

        if ($recentLogins >= 5) {
            $this->logSecurityEvent('unusual_access_pattern', [
                'user_id' => $userId,
                'unique_ips_24h' => $recentLogins,
            ], 'medium');
        }
    }
}
<?php

namespace App\Http\Livewire\Admin;

use App\Services\SecurityMonitoringService;
use App\Services\AuditService;
use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;

class SecurityDashboard extends Component
{
    use WithPagination;

    public $timeRange = '24'; // hours
    public $riskLevelFilter = '';
    public $alertTypeFilter = '';
    public $refreshInterval = 30; // seconds

    protected SecurityMonitoringService $securityService;
    protected AuditService $auditService;

    public function boot(SecurityMonitoringService $securityService, AuditService $auditService)
    {
        $this->securityService = $securityService;
        $this->auditService = $auditService;
    }

    public function mount()
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function render()
    {
        $securitySummary = $this->getSecuritySummary();
        $recentAlerts = $this->getRecentSecurityAlerts();
        $systemAnomalies = $this->getSystemAnomalies();
        $riskMetrics = $this->getRiskMetrics();

        return view('livewire.admin.security-dashboard', [
            'securitySummary' => $securitySummary,
            'recentAlerts' => $recentAlerts,
            'systemAnomalies' => $systemAnomalies,
            'riskMetrics' => $riskMetrics,
            'alertStats' => $this->getAlertStatistics()
        ]);
    }

    public function updatedTimeRange()
    {
        $this->resetPage();
    }

    public function updatedRiskLevelFilter()
    {
        $this->resetPage();
    }

    public function updatedAlertTypeFilter()
    {
        $this->resetPage();
    }

    public function runAnomalyDetection()
    {
        try {
            $anomalies = $this->securityService->detectSystemAnomalies();
            
            if (empty($anomalies)) {
                $this->dispatchBrowserEvent('show-notification', [
                    'type' => 'success',
                    'message' => 'No security anomalies detected.'
                ]);
            } else {
                $alertsGenerated = 0;
                foreach ($anomalies as $anomaly) {
                    if ($anomaly['severity'] === 'high' || $anomaly['severity'] === 'critical') {
                        $this->generateAnomalyAlert($anomaly);
                        $alertsGenerated++;
                    }
                }

                $message = count($anomalies) . ' anomalies detected';
                if ($alertsGenerated > 0) {
                    $message .= ", {$alertsGenerated} alerts generated";
                }

                $this->dispatchBrowserEvent('show-notification', [
                    'type' => 'warning',
                    'message' => $message
                ]);
            }

            // Clear cache to refresh data
            $this->clearSecurityCache();

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'Failed to run anomaly detection: ' . $e->getMessage()
            ]);
        }
    }

    public function acknowledgeAlert($alertId)
    {
        try {
            $alert = AuditLog::findOrFail($alertId);
            
            // Update alert to mark as acknowledged
            $additionalData = $alert->additional_data ?? [];
            $additionalData['acknowledged'] = true;
            $additionalData['acknowledged_by'] = auth()->id();
            $additionalData['acknowledged_at'] = now()->toISOString();
            
            $alert->update(['additional_data' => $additionalData]);

            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'success',
                'message' => 'Alert acknowledged successfully.'
            ]);

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'Failed to acknowledge alert: ' . $e->getMessage()
            ]);
        }
    }

    public function refreshDashboard()
    {
        $this->clearSecurityCache();
        $this->dispatchBrowserEvent('show-notification', [
            'type' => 'success',
            'message' => 'Dashboard refreshed successfully.'
        ]);
    }

    protected function getSecuritySummary(): array
    {
        $cacheKey = "security_summary_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            return $this->auditService->getSecurityEventsSummary((int) $this->timeRange);
        });
    }

    protected function getRecentSecurityAlerts()
    {
        $query = AuditLog::where('event_type', 'security_event')
            ->where('created_at', '>=', now()->subHours((int) $this->timeRange))
            ->orderBy('created_at', 'desc');

        if ($this->riskLevelFilter) {
            $query->whereJsonContains('additional_data->risk_level', $this->riskLevelFilter);
        }

        if ($this->alertTypeFilter) {
            $query->where('action', $this->alertTypeFilter);
        }

        return $query->paginate(10);
    }

    protected function getSystemAnomalies(): array
    {
        $cacheKey = "system_anomalies_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 600, function () {
            return $this->securityService->detectSystemAnomalies();
        });
    }

    protected function getRiskMetrics(): array
    {
        $cacheKey = "risk_metrics_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            $since = now()->subHours((int) $this->timeRange);
            
            $alerts = AuditLog::where('event_type', 'security_event')
                ->where('action', 'security_alert_generated')
                ->where('created_at', '>=', $since)
                ->get();

            $riskLevels = $alerts->pluck('additional_data.risk_level')->countBy();
            $avgRiskScore = $alerts->avg('additional_data.risk_score') ?? 0;
            
            return [
                'total_alerts' => $alerts->count(),
                'average_risk_score' => round($avgRiskScore, 1),
                'risk_distribution' => $riskLevels->toArray(),
                'high_risk_alerts' => $riskLevels->get('high', 0) + $riskLevels->get('critical', 0),
                'trend' => $this->calculateRiskTrend()
            ];
        });
    }

    protected function getAlertStatistics(): array
    {
        $cacheKey = "alert_statistics_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            $since = now()->subHours((int) $this->timeRange);
            
            return [
                'failed_logins' => AuditLog::where('event_type', 'authentication')
                    ->where('action', 'failed_login')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'suspicious_activities' => AuditLog::where('event_type', 'security_event')
                    ->where('action', 'suspicious_activity_detected')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'unauthorized_attempts' => AuditLog::where('event_type', 'security_event')
                    ->where('action', 'unauthorized_access')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'account_lockouts' => AuditLog::where('event_type', 'security_event')
                    ->where('action', 'account_lockout')
                    ->where('created_at', '>=', $since)
                    ->count()
            ];
        });
    }

    protected function calculateRiskTrend(): string
    {
        $currentPeriod = now()->subHours((int) $this->timeRange);
        $previousPeriod = now()->subHours((int) $this->timeRange * 2);
        
        $currentAlerts = AuditLog::where('event_type', 'security_event')
            ->where('created_at', '>=', $currentPeriod)
            ->count();
            
        $previousAlerts = AuditLog::where('event_type', 'security_event')
            ->where('created_at', '>=', $previousPeriod)
            ->where('created_at', '<', $currentPeriod)
            ->count();

        if ($previousAlerts === 0) {
            return $currentAlerts > 0 ? 'increasing' : 'stable';
        }

        $change = (($currentAlerts - $previousAlerts) / $previousAlerts) * 100;
        
        if ($change > 10) return 'increasing';
        if ($change < -10) return 'decreasing';
        return 'stable';
    }

    protected function generateAnomalyAlert(array $anomaly): void
    {
        $riskScore = match ($anomaly['severity']) {
            'critical' => 95,
            'high' => 80,
            'medium' => 60,
            'low' => 30,
            default => 25
        };
        
        $alertData = [
            'risk_score' => $riskScore,
            'risk_level' => $anomaly['severity'],
            'alerts' => [$anomaly['description']],
            'analysis_type' => 'system_anomaly',
            'anomaly_type' => $anomaly['type'],
            'anomaly_count' => $anomaly['count'] ?? null,
            'detection_time' => now(),
            'automated_detection' => true
        ];

        $this->securityService->generateSecurityAlert($alertData);
    }

    protected function clearSecurityCache(): void
    {
        $cacheKeys = [
            "security_summary_{$this->timeRange}",
            "system_anomalies_{$this->timeRange}",
            "risk_metrics_{$this->timeRange}",
            "alert_statistics_{$this->timeRange}"
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
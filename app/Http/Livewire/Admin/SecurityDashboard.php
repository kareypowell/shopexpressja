<?php

namespace App\Http\Livewire\Admin;

use App\Services\SecurityService;
use App\Services\AuditCacheService;
use Livewire\Component;

class SecurityDashboard extends Component
{
    public $timeRange = 24; // hours
    public $riskLevel = 'all';
    public $alertType = 'all';
    
    public $securityData = [];
    public $isLoading = false;

    protected SecurityService $securityService;
    protected AuditCacheService $cacheService;

    public function boot(SecurityService $securityService, AuditCacheService $cacheService)
    {
        $this->securityService = $securityService;
        $this->cacheService = $cacheService;
    }

    public function mount()
    {
        $this->loadSecurityData();
    }

    public function updatedTimeRange()
    {
        $this->loadSecurityData();
    }

    public function updatedRiskLevel()
    {
        $this->loadSecurityData();
    }

    public function updatedAlertType()
    {
        $this->loadSecurityData();
    }

    public function refreshDashboard()
    {
        $this->isLoading = true;
        
        // Clear relevant caches
        $this->cacheService->invalidateStatisticsCaches();
        
        $this->loadSecurityData();
        
        $this->isLoading = false;
        
        $this->dispatchBrowserEvent('dashboard-refreshed');
    }

    public function loadSecurityData()
    {
        try {
            $this->securityData = $this->securityService->getSecurityDashboardData($this->timeRange);
            
            // Filter alerts based on selected criteria
            if ($this->riskLevel !== 'all') {
                $riskLevel = $this->riskLevel;
                $this->securityData['recent_alerts'] = array_filter(
                    $this->securityData['recent_alerts'],
                    function($alert) use ($riskLevel) {
                        return strtolower($alert['risk_level']) === strtolower($riskLevel);
                    }
                );
            }
            
            if ($this->alertType !== 'all') {
                $alertType = $this->alertType;
                $this->securityData['recent_alerts'] = array_filter(
                    $this->securityData['recent_alerts'],
                    function($alert) use ($alertType) {
                        return strpos(strtolower($alert['type']), strtolower($alertType)) !== false;
                    }
                );
            }
            
        } catch (\Exception $e) {
            \Log::error('Security dashboard data loading failed', [
                'error' => $e->getMessage(),
                'time_range' => $this->timeRange
            ]);
            
            // Provide fallback data
            $this->securityData = [
                'security_events' => 0,
                'failed_logins' => 0,
                'suspicious_activities' => 0,
                'unique_ips' => 0,
                'risk_assessment' => [
                    'level' => 'Minimal',
                    'score' => 0,
                    'high_risk_alerts' => 0,
                    'trend' => 'Stable',
                ],
                'recent_alerts' => [],
            ];
        }
    }

    public function getTimeRangeOptions()
    {
        return [
            1 => 'Last Hour',
            6 => 'Last 6 Hours',
            24 => 'Last 24 Hours',
            72 => 'Last 3 Days',
            168 => 'Last Week',
        ];
    }

    public function getRiskLevelOptions()
    {
        return [
            'all' => 'All Levels',
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            'minimal' => 'Minimal',
        ];
    }

    public function getAlertTypeOptions()
    {
        return [
            'all' => 'All Types',
            'authentication' => 'Authentication',
            'suspicious' => 'Suspicious Activity',
            'security' => 'Security Alert',
            'access' => 'Access Pattern',
        ];
    }

    public function getRiskLevelColor($level)
    {
        switch (strtolower($level)) {
            case 'critical':
                return 'text-red-600 bg-red-100';
            case 'high':
                return 'text-red-500 bg-red-50';
            case 'medium':
                return 'text-yellow-600 bg-yellow-100';
            case 'low':
                return 'text-blue-600 bg-blue-100';
            case 'minimal':
                return 'text-green-600 bg-green-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    }

    public function getTrendIcon($trend)
    {
        switch (strtolower($trend)) {
            case 'increasing':
                return '↗';
            case 'decreasing':
                return '↘';
            default:
                return '→';
        }
    }

    public function getTrendColor($trend)
    {
        switch (strtolower($trend)) {
            case 'increasing':
                return 'text-red-500';
            case 'decreasing':
                return 'text-green-500';
            default:
                return 'text-gray-500';
        }
    }

    public function render()
    {
        return view('livewire.admin.security-dashboard', [
            'timeRangeOptions' => $this->getTimeRangeOptions(),
            'riskLevelOptions' => $this->getRiskLevelOptions(),
            'alertTypeOptions' => $this->getAlertTypeOptions(),
        ]);
    }
}
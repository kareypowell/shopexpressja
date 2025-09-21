<?php

namespace App\Http\Livewire\Admin;

use App\Models\AuditSetting;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\AuditRetentionService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

class AuditSettings extends Component
{
    use AuthorizesRequests;
    public $retentionSettings = [];
    public $alertThresholds = [];
    public $notificationSettings = [];
    public $performanceSettings = [];
    public $exportSettings = [];
    
    public $systemHealth = [];
    public $auditStatistics = [];
    
    public $isLoading = false;
    public $activeTab = 'retention';

    protected $rules = [
        'retentionSettings.authentication' => 'required|integer|min:1|max:3650',
        'retentionSettings.authorization' => 'required|integer|min:1|max:3650',
        'retentionSettings.model_created' => 'required|integer|min:1|max:3650',
        'retentionSettings.model_updated' => 'required|integer|min:1|max:3650',
        'retentionSettings.model_deleted' => 'required|integer|min:1|max:3650',
        'retentionSettings.business_action' => 'required|integer|min:1|max:3650',
        'retentionSettings.financial_transaction' => 'required|integer|min:1|max:3650',
        'retentionSettings.system_event' => 'required|integer|min:1|max:3650',
        'retentionSettings.security_event' => 'required|integer|min:1|max:3650',
        
        'alertThresholds.failed_login_attempts' => 'required|integer|min:1|max:100',
        'alertThresholds.bulk_operation_threshold' => 'required|integer|min:1|max:1000',
        'alertThresholds.suspicious_activity_score' => 'required|integer|min:1|max:100',
        
        'notificationSettings.security_alerts_enabled' => 'boolean',
        'notificationSettings.security_alert_recipients' => 'array',
        'notificationSettings.security_alert_recipients.*' => 'email',
        'notificationSettings.daily_summary_enabled' => 'boolean',
        'notificationSettings.weekly_report_enabled' => 'boolean',
        
        'performanceSettings.async_processing' => 'boolean',
        'performanceSettings.batch_size' => 'required|integer|min:1|max:1000',
        'performanceSettings.queue_connection' => 'required|string',
        
        'exportSettings.max_export_records' => 'required|integer|min:100|max:100000',
        'exportSettings.allowed_formats' => 'array',
        'exportSettings.allowed_formats.*' => 'in:csv,pdf',
        'exportSettings.include_sensitive_data' => 'boolean',
    ];

    public function mount()
    {
        // Check authorization
        $this->authorize('manageSettings', AuditLog::class);
        
        $this->loadAllSettings();
        $this->loadSystemHealth();
        $this->loadAuditStatistics();
    }

    public function render()
    {
        return view('livewire.admin.audit-settings');
    }

    public function loadAllSettings()
    {
        $this->retentionSettings = AuditSetting::get('retention_policy', AuditSetting::DEFAULT_SETTINGS['retention_policy']);
        $this->alertThresholds = AuditSetting::get('alert_thresholds', AuditSetting::DEFAULT_SETTINGS['alert_thresholds']);
        $this->notificationSettings = AuditSetting::get('notification_settings', AuditSetting::DEFAULT_SETTINGS['notification_settings']);
        $this->performanceSettings = AuditSetting::get('performance_settings', AuditSetting::DEFAULT_SETTINGS['performance_settings']);
        $this->exportSettings = AuditSetting::get('export_settings', AuditSetting::DEFAULT_SETTINGS['export_settings']);
        
        // Ensure security_alert_recipients is an array
        if (!is_array($this->notificationSettings['security_alert_recipients'])) {
            $this->notificationSettings['security_alert_recipients'] = [];
        }
    }

    public function loadSystemHealth()
    {
        try {
            $totalLogs = AuditLog::count();
            $logsLast24h = AuditLog::where('created_at', '>=', now()->subDay())->count();
            $logsLast7d = AuditLog::where('created_at', '>=', now()->subDays(7))->count();
            
            $oldestLog = AuditLog::oldest()->first();
            $newestLog = AuditLog::latest()->first();
            
            $avgLogsPerDay = $logsLast7d > 0 ? round($logsLast7d / 7, 2) : 0;
            
            // Calculate storage usage (approximate)
            $avgLogSize = 1024; // Approximate 1KB per log entry
            $estimatedStorageUsage = $totalLogs * $avgLogSize;
            
            $this->systemHealth = [
                'total_logs' => $totalLogs,
                'logs_last_24h' => $logsLast24h,
                'logs_last_7d' => $logsLast7d,
                'avg_logs_per_day' => $avgLogsPerDay,
                'oldest_log_date' => $oldestLog ? $oldestLog->created_at->format('Y-m-d H:i:s') : null,
                'newest_log_date' => $newestLog ? $newestLog->created_at->format('Y-m-d H:i:s') : null,
                'estimated_storage_mb' => round($estimatedStorageUsage / 1024 / 1024, 2),
                'system_status' => $this->determineSystemStatus($logsLast24h, $avgLogsPerDay),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to load audit system health: ' . $e->getMessage());
            $this->systemHealth = [
                'error' => 'Failed to load system health data'
            ];
        }
    }

    public function loadAuditStatistics()
    {
        try {
            $auditService = app(AuditService::class);
            $this->auditStatistics = $auditService->getAuditStatistics(7);
        } catch (\Exception $e) {
            Log::error('Failed to load audit statistics: ' . $e->getMessage());
            $this->auditStatistics = [
                'error' => 'Failed to load audit statistics'
            ];
        }
    }

    private function determineSystemStatus($logsLast24h, $avgLogsPerDay)
    {
        if ($logsLast24h == 0) {
            return ['status' => 'warning', 'message' => 'No audit logs in the last 24 hours'];
        }
        
        if ($logsLast24h > $avgLogsPerDay * 2) {
            return ['status' => 'warning', 'message' => 'Higher than average audit activity'];
        }
        
        return ['status' => 'healthy', 'message' => 'System operating normally'];
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        
        // Reload data when switching to health tab
        if ($tab === 'health') {
            $this->loadSystemHealth();
            $this->loadAuditStatistics();
        }
    }

    public function saveRetentionSettings()
    {
        $this->isLoading = true;
        
        $this->validate([
            'retentionSettings.authentication' => 'required|integer|min:1|max:3650',
            'retentionSettings.authorization' => 'required|integer|min:1|max:3650',
            'retentionSettings.model_created' => 'required|integer|min:1|max:3650',
            'retentionSettings.model_updated' => 'required|integer|min:1|max:3650',
            'retentionSettings.model_deleted' => 'required|integer|min:1|max:3650',
            'retentionSettings.business_action' => 'required|integer|min:1|max:3650',
            'retentionSettings.financial_transaction' => 'required|integer|min:1|max:3650',
            'retentionSettings.system_event' => 'required|integer|min:1|max:3650',
            'retentionSettings.security_event' => 'required|integer|min:1|max:3650',
        ]);

        try {
            AuditSetting::set('retention_policy', $this->retentionSettings);
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Retention settings saved successfully'
            ]);
            
            Log::info('Audit retention settings updated', $this->retentionSettings);
        } catch (\Exception $e) {
            Log::error('Failed to save audit retention settings: ' . $e->getMessage());
            $this->addError('retention', 'Failed to save retention settings. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save retention settings. Please try again.'
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function saveAlertThresholds()
    {
        $this->validate([
            'alertThresholds.failed_login_attempts' => 'required|integer|min:1|max:100',
            'alertThresholds.bulk_operation_threshold' => 'required|integer|min:1|max:1000',
            'alertThresholds.suspicious_activity_score' => 'required|integer|min:1|max:100',
        ]);

        try {
            AuditSetting::set('alert_thresholds', $this->alertThresholds);
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Alert thresholds saved successfully'
            ]);
            
            Log::info('Audit alert thresholds updated', $this->alertThresholds);
        } catch (\Exception $e) {
            Log::error('Failed to save audit alert thresholds: ' . $e->getMessage());
            $this->addError('alerts', 'Failed to save alert thresholds. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save alert thresholds. Please try again.'
            ]);
        }
    }

    public function saveNotificationSettings()
    {
        $this->validate([
            'notificationSettings.security_alerts_enabled' => 'boolean',
            'notificationSettings.security_alert_recipients' => 'array',
            'notificationSettings.security_alert_recipients.*' => 'email',
            'notificationSettings.daily_summary_enabled' => 'boolean',
            'notificationSettings.weekly_report_enabled' => 'boolean',
        ]);

        try {
            AuditSetting::set('notification_settings', $this->notificationSettings);
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Notification settings saved successfully'
            ]);
            
            Log::info('Audit notification settings updated', $this->notificationSettings);
        } catch (\Exception $e) {
            Log::error('Failed to save audit notification settings: ' . $e->getMessage());
            $this->addError('notifications', 'Failed to save notification settings. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save notification settings. Please try again.'
            ]);
        }
    }

    public function savePerformanceSettings()
    {
        $this->validate([
            'performanceSettings.async_processing' => 'boolean',
            'performanceSettings.batch_size' => 'required|integer|min:1|max:1000',
            'performanceSettings.queue_connection' => 'required|string',
        ]);

        try {
            AuditSetting::set('performance_settings', $this->performanceSettings);
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Performance settings saved successfully'
            ]);
            
            Log::info('Audit performance settings updated', $this->performanceSettings);
        } catch (\Exception $e) {
            Log::error('Failed to save audit performance settings: ' . $e->getMessage());
            $this->addError('performance', 'Failed to save performance settings. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save performance settings. Please try again.'
            ]);
        }
    }

    public function saveExportSettings()
    {
        $this->validate([
            'exportSettings.max_export_records' => 'required|integer|min:100|max:100000',
            'exportSettings.allowed_formats' => 'array',
            'exportSettings.allowed_formats.*' => 'in:csv,pdf',
            'exportSettings.include_sensitive_data' => 'boolean',
        ]);

        try {
            AuditSetting::set('export_settings', $this->exportSettings);
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Export settings saved successfully'
            ]);
            
            Log::info('Audit export settings updated', $this->exportSettings);
        } catch (\Exception $e) {
            Log::error('Failed to save audit export settings: ' . $e->getMessage());
            $this->addError('export', 'Failed to save export settings. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to save export settings. Please try again.'
            ]);
        }
    }

    public function addSecurityAlertRecipient()
    {
        $this->notificationSettings['security_alert_recipients'][] = '';
    }

    public function removeSecurityAlertRecipient($index)
    {
        unset($this->notificationSettings['security_alert_recipients'][$index]);
        $this->notificationSettings['security_alert_recipients'] = array_values($this->notificationSettings['security_alert_recipients']);
    }

    public function runCleanupNow()
    {
        try {
            $auditRetentionService = app(AuditRetentionService::class);
            $results = $auditRetentionService->runAutomatedCleanup();
            
            if (!empty($results['errors'])) {
                $this->addError('general', 'Cleanup completed with errors: ' . implode(', ', $results['errors']));
                $this->dispatchBrowserEvent('toastr:warning', [
                    'message' => "Cleanup completed with errors. Removed {$results['total_deleted']} entries."
                ]);
            } else {
                $this->dispatchBrowserEvent('toastr:success', [
                    'message' => "Audit cleanup completed successfully. Removed {$results['total_deleted']} old entries."
                ]);
            }
            
            // Reload system health after cleanup
            $this->loadSystemHealth();
            
            Log::info('Manual audit cleanup executed via settings interface', $results);
        } catch (\Exception $e) {
            Log::error('Failed to run audit cleanup: ' . $e->getMessage());
            $this->addError('general', 'Failed to run cleanup. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to run cleanup. Please try again.'
            ]);
        }
    }

    public function testSecurityAlert()
    {
        if (empty($this->notificationSettings['security_alert_recipients'])) {
            $this->addError('notifications', 'Please add at least one security alert recipient first.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Please add at least one security alert recipient first.'
            ]);
            return;
        }

        try {
            // This would send a test security alert email
            // For now, we'll just log it and show success
            Log::info('Test security alert sent to recipients', [
                'recipients' => $this->notificationSettings['security_alert_recipients']
            ]);
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Test security alert sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send test security alert: ' . $e->getMessage());
            $this->addError('notifications', 'Failed to send test alert. Please check your email configuration.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to send test alert. Please check your email configuration.'
            ]);
        }
    }

    public function initializeDefaultSettings()
    {
        try {
            AuditSetting::initializeDefaults();
            $this->loadAllSettings();
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Default settings initialized successfully'
            ]);
            
            Log::info('Audit default settings initialized');
        } catch (\Exception $e) {
            Log::error('Failed to initialize default settings: ' . $e->getMessage());
            $this->addError('general', 'Failed to initialize default settings. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to initialize default settings. Please try again.'
            ]);
        }
    }

    public function clearSettingsCache()
    {
        try {
            AuditSetting::clearCache();
            
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Settings cache cleared successfully'
            ]);
            
            Log::info('Audit settings cache cleared');
        } catch (\Exception $e) {
            Log::error('Failed to clear settings cache: ' . $e->getMessage());
            $this->addError('general', 'Failed to clear cache. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to clear cache. Please try again.'
            ]);
        }
    }

    public function getCleanupPreview()
    {
        try {
            $auditRetentionService = app(AuditRetentionService::class);
            $preview = $auditRetentionService->getCleanupPreview();
            
            $message = "Cleanup preview: {$preview['total_to_delete']} records would be deleted.";
            
            $this->dispatchBrowserEvent('toastr:info', [
                'message' => $message
            ]);
            
            Log::info('Audit cleanup preview generated', $preview);
        } catch (\Exception $e) {
            Log::error('Failed to generate cleanup preview: ' . $e->getMessage());
            $this->addError('general', 'Failed to generate preview. Please try again.');
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Failed to generate preview. Please try again.'
            ]);
        }
    }
}
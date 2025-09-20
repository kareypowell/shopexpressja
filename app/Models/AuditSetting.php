<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AuditSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'setting_value' => 'array',
    ];

    /**
     * Cache key prefix for settings.
     */
    const CACHE_PREFIX = 'audit_setting_';

    /**
     * Cache TTL in seconds (1 hour).
     */
    const CACHE_TTL = 3600;

    /**
     * Default audit settings.
     */
    const DEFAULT_SETTINGS = [
        'retention_policy' => [
            'authentication' => 365, // days
            'authorization' => 365,
            'model_created' => 180,
            'model_updated' => 180,
            'model_deleted' => 365,
            'business_action' => 180,
            'financial_transaction' => 2555, // 7 years for compliance
            'system_event' => 90,
            'security_event' => 365,
        ],
        'alert_thresholds' => [
            'failed_login_attempts' => 5,
            'bulk_operation_threshold' => 50,
            'suspicious_activity_score' => 75,
        ],
        'notification_settings' => [
            'security_alerts_enabled' => true,
            'security_alert_recipients' => [],
            'daily_summary_enabled' => false,
            'weekly_report_enabled' => false,
        ],
        'performance_settings' => [
            'async_processing' => true,
            'batch_size' => 100,
            'queue_connection' => 'default',
        ],
        'export_settings' => [
            'max_export_records' => 10000,
            'allowed_formats' => ['csv', 'pdf'],
            'include_sensitive_data' => false,
        ],
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = static::where('setting_key', $key)->first();
            
            if ($setting) {
                return $setting->setting_value;
            }
            
            // Return default value from DEFAULT_SETTINGS if available
            if (isset(self::DEFAULT_SETTINGS[$key])) {
                return self::DEFAULT_SETTINGS[$key];
            }
            
            return $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value): self
    {
        $setting = static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * Get retention policy for a specific event type.
     */
    public static function getRetentionDays(string $eventType): int
    {
        $retentionPolicy = self::get('retention_policy', self::DEFAULT_SETTINGS['retention_policy']);
        
        return $retentionPolicy[$eventType] ?? 90; // Default 90 days
    }

    /**
     * Get alert threshold for a specific type.
     */
    public static function getAlertThreshold(string $type): int
    {
        $alertThresholds = self::get('alert_thresholds', self::DEFAULT_SETTINGS['alert_thresholds']);
        
        return $alertThresholds[$type] ?? 0;
    }

    /**
     * Check if security alerts are enabled.
     */
    public static function areSecurityAlertsEnabled(): bool
    {
        $notificationSettings = self::get('notification_settings', self::DEFAULT_SETTINGS['notification_settings']);
        
        return $notificationSettings['security_alerts_enabled'] ?? true;
    }

    /**
     * Get security alert recipients.
     */
    public static function getSecurityAlertRecipients(): array
    {
        $notificationSettings = self::get('notification_settings', self::DEFAULT_SETTINGS['notification_settings']);
        
        return $notificationSettings['security_alert_recipients'] ?? [];
    }

    /**
     * Check if async processing is enabled.
     */
    public static function isAsyncProcessingEnabled(): bool
    {
        $performanceSettings = self::get('performance_settings', self::DEFAULT_SETTINGS['performance_settings']);
        
        return $performanceSettings['async_processing'] ?? true;
    }

    /**
     * Get batch size for processing.
     */
    public static function getBatchSize(): int
    {
        $performanceSettings = self::get('performance_settings', self::DEFAULT_SETTINGS['performance_settings']);
        
        return $performanceSettings['batch_size'] ?? 100;
    }

    /**
     * Get maximum export records limit.
     */
    public static function getMaxExportRecords(): int
    {
        $exportSettings = self::get('export_settings', self::DEFAULT_SETTINGS['export_settings']);
        
        return $exportSettings['max_export_records'] ?? 10000;
    }

    /**
     * Get allowed export formats.
     */
    public static function getAllowedExportFormats(): array
    {
        $exportSettings = self::get('export_settings', self::DEFAULT_SETTINGS['export_settings']);
        
        return $exportSettings['allowed_formats'] ?? ['csv', 'pdf'];
    }

    /**
     * Initialize default settings.
     */
    public static function initializeDefaults(): void
    {
        foreach (self::DEFAULT_SETTINGS as $key => $value) {
            if (!static::where('setting_key', $key)->exists()) {
                static::create([
                    'setting_key' => $key,
                    'setting_value' => $value,
                ]);
            }
        }
    }

    /**
     * Clear all cached settings.
     */
    public static function clearCache(): void
    {
        foreach (array_keys(self::DEFAULT_SETTINGS) as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are updated
        static::saved(function ($model) {
            Cache::forget(self::CACHE_PREFIX . $model->setting_key);
        });

        static::deleted(function ($model) {
            Cache::forget(self::CACHE_PREFIX . $model->setting_key);
        });
    }
}

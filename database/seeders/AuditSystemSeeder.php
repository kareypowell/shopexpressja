<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuditSetting;

class AuditSystemSeeder extends Seeder
{
    /**
     * Seed the audit system with initial configuration settings.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Seeding audit system configuration...');

        // Retention policies (in days)
        $retentionPolicies = [
            'retention_authentication' => 365,      // 1 year for authentication events
            'retention_security_events' => 1095,    // 3 years for security events
            'retention_model_changes' => 730,       // 2 years for model changes
            'retention_business_actions' => 1095,   // 3 years for business actions
            'retention_financial_transactions' => 2555, // 7 years for financial records
            'retention_system_events' => 365,       // 1 year for system events
            'retention_default' => 365,             // Default 1 year retention
        ];

        foreach ($retentionPolicies as $key => $days) {
            AuditSetting::updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => json_encode([
                        'days' => $days,
                        'enabled' => true,
                        'auto_cleanup' => true,
                        'description' => $this->getRetentionDescription($key)
                    ])
                ]
            );
        }

        // Security monitoring thresholds
        $securitySettings = [
            'failed_login_threshold' => [
                'threshold' => 5,
                'time_window_minutes' => 15,
                'enabled' => true,
                'alert_email' => true,
                'description' => 'Alert after 5 failed login attempts within 15 minutes'
            ],
            'suspicious_activity_threshold' => [
                'threshold' => 10,
                'time_window_minutes' => 60,
                'enabled' => true,
                'alert_email' => true,
                'description' => 'Alert after 10 suspicious activities within 1 hour'
            ],
            'bulk_operation_threshold' => [
                'threshold' => 50,
                'time_window_minutes' => 30,
                'enabled' => true,
                'alert_email' => true,
                'description' => 'Alert for bulk operations exceeding 50 records in 30 minutes'
            ],
            'ip_change_alert' => [
                'enabled' => true,
                'alert_email' => true,
                'whitelist_internal' => true,
                'description' => 'Alert when user IP address changes significantly'
            ],
            'unauthorized_access_alert' => [
                'enabled' => true,
                'alert_email' => true,
                'immediate_alert' => true,
                'description' => 'Immediate alert for unauthorized access attempts'
            ]
        ];

        foreach ($securitySettings as $key => $settings) {
            AuditSetting::updateOrCreate(
                ['setting_key' => "security_monitoring_{$key}"],
                ['setting_value' => json_encode($settings)]
            );
        }

        // Export and reporting settings
        $exportSettings = [
            'max_export_records' => [
                'limit' => 10000,
                'enabled' => true,
                'formats' => ['csv', 'pdf'],
                'description' => 'Maximum records per export operation'
            ],
            'report_generation' => [
                'enabled' => true,
                'max_date_range_days' => 365,
                'include_sensitive_data' => false,
                'description' => 'Audit report generation settings'
            ],
            'scheduled_reports' => [
                'enabled' => false,
                'frequency' => 'monthly',
                'recipients' => [],
                'description' => 'Automated audit report scheduling'
            ]
        ];

        foreach ($exportSettings as $key => $settings) {
            AuditSetting::updateOrCreate(
                ['setting_key' => "export_{$key}"],
                ['setting_value' => json_encode($settings)]
            );
        }

        // Performance and caching settings
        $performanceSettings = [
            'cache_enabled' => [
                'enabled' => true,
                'ttl_seconds' => 3600,
                'cache_summaries' => true,
                'description' => 'Enable caching for audit log summaries'
            ],
            'async_processing' => [
                'enabled' => true,
                'queue_name' => 'audit-processing',
                'batch_size' => 100,
                'description' => 'Asynchronous audit log processing'
            ],
            'database_optimization' => [
                'enabled' => true,
                'index_optimization' => true,
                'query_caching' => true,
                'description' => 'Database performance optimizations'
            ]
        ];

        foreach ($performanceSettings as $key => $settings) {
            AuditSetting::updateOrCreate(
                ['setting_key' => "performance_{$key}"],
                ['setting_value' => json_encode($settings)]
            );
        }

        // Notification settings
        $notificationSettings = [
            'email_notifications' => [
                'enabled' => true,
                'security_alerts' => true,
                'system_health' => true,
                'recipients' => [
                    'security@shipsharkltd.com',
                    'admin@shipsharkltd.com'
                ],
                'description' => 'Email notification configuration'
            ],
            'alert_channels' => [
                'email' => true,
                'slack' => false,
                'webhook' => false,
                'description' => 'Available alert notification channels'
            ]
        ];

        foreach ($notificationSettings as $key => $settings) {
            AuditSetting::updateOrCreate(
                ['setting_key' => "notifications_{$key}"],
                ['setting_value' => json_encode($settings)]
            );
        }

        // System configuration
        $systemSettings = [
            'audit_system_enabled' => [
                'enabled' => true,
                'version' => '1.0.0',
                'last_updated' => now()->toISOString(),
                'description' => 'Master audit system enable/disable switch'
            ],
            'data_integrity' => [
                'checksums_enabled' => true,
                'tamper_detection' => true,
                'immutable_logs' => true,
                'description' => 'Data integrity and security features'
            ],
            'compliance_mode' => [
                'enabled' => false,
                'standard' => 'SOX',
                'extended_retention' => true,
                'description' => 'Compliance-specific audit requirements'
            ]
        ];

        foreach ($systemSettings as $key => $settings) {
            AuditSetting::updateOrCreate(
                ['setting_key' => "system_{$key}"],
                ['setting_value' => json_encode($settings)]
            );
        }

        // Auditable models configuration
        $auditableModels = [
            'auditable_models' => [
                'User' => [
                    'enabled' => true,
                    'excluded_fields' => ['password', 'remember_token', 'email_verified_at'],
                    'track_relationships' => true
                ],
                'Package' => [
                    'enabled' => true,
                    'excluded_fields' => [],
                    'track_relationships' => true
                ],
                'ConsolidatedPackage' => [
                    'enabled' => true,
                    'excluded_fields' => [],
                    'track_relationships' => true
                ],
                'Manifest' => [
                    'enabled' => true,
                    'excluded_fields' => [],
                    'track_relationships' => true
                ],
                'CustomerTransaction' => [
                    'enabled' => true,
                    'excluded_fields' => [],
                    'track_relationships' => true
                ],
                'PackageDistribution' => [
                    'enabled' => true,
                    'excluded_fields' => [],
                    'track_relationships' => true
                ],
                'Role' => [
                    'enabled' => true,
                    'excluded_fields' => [],
                    'track_relationships' => true
                ],
                'Office' => [
                    'enabled' => true,
                    'excluded_fields' => [],
                    'track_relationships' => false
                ],
                'BroadcastMessage' => [
                    'enabled' => true,
                    'excluded_fields' => [],
                    'track_relationships' => true
                ]
            ]
        ];

        AuditSetting::updateOrCreate(
            ['setting_key' => 'auditable_models'],
            ['setting_value' => json_encode($auditableModels['auditable_models'])]
        );

        $this->command->info('Audit system configuration seeded successfully!');
        $this->command->info('Total settings created: ' . AuditSetting::count());
    }

    /**
     * Get description for retention policy setting.
     *
     * @param string $key
     * @return string
     */
    private function getRetentionDescription($key)
    {
        $descriptions = [
            'retention_authentication' => 'Retention period for login/logout and authentication events',
            'retention_security_events' => 'Retention period for security alerts and suspicious activities',
            'retention_model_changes' => 'Retention period for data model create/update/delete operations',
            'retention_business_actions' => 'Retention period for business operations like consolidation and manifest management',
            'retention_financial_transactions' => 'Retention period for financial transactions and payment processing',
            'retention_system_events' => 'Retention period for system maintenance and administrative events',
            'retention_default' => 'Default retention period for events not covered by specific policies'
        ];

        return $descriptions[$key] ?? 'Audit log retention policy';
    }
}
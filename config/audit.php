<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the ShipSharkLtd audit logging
    | system. These settings control how audit logs are created, stored,
    | processed, and maintained.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Audit System Enable/Disable
    |--------------------------------------------------------------------------
    |
    | This option controls whether the audit system is enabled. When disabled,
    | no audit logs will be created, but the system will continue to function
    | normally.
    |
    */

    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Processing
    |--------------------------------------------------------------------------
    |
    | When enabled, audit logs will be processed asynchronously using queues.
    | This improves application performance by not blocking user operations
    | while audit logs are being created.
    |
    */

    'async_enabled' => env('AUDIT_ASYNC_ENABLED', true),
    'queue_name' => env('AUDIT_QUEUE_NAME', 'audit-processing'),
    'batch_size' => env('AUDIT_BATCH_SIZE', 100),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the database connection and table names for audit logs.
    | You can use a separate database connection for audit logs if needed.
    |
    */

    'database' => [
        'connection' => env('AUDIT_DB_CONNECTION', null), // null uses default connection
        'audit_logs_table' => 'audit_logs',
        'audit_settings_table' => 'audit_settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Policies
    |--------------------------------------------------------------------------
    |
    | Configure how long different types of audit logs should be retained.
    | Values are in days. Set to 0 to disable automatic cleanup for that type.
    |
    */

    'retention' => [
        'authentication' => env('AUDIT_RETENTION_AUTHENTICATION', 365),
        'security_events' => env('AUDIT_RETENTION_SECURITY_EVENTS', 1095),
        'model_changes' => env('AUDIT_RETENTION_MODEL_CHANGES', 730),
        'business_actions' => env('AUDIT_RETENTION_BUSINESS_ACTIONS', 1095),
        'financial_transactions' => env('AUDIT_RETENTION_FINANCIAL_TRANSACTIONS', 2555),
        'system_events' => env('AUDIT_RETENTION_SYSTEM_EVENTS', 365),
        'default' => env('AUDIT_RETENTION_DEFAULT', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure thresholds and settings for security monitoring and alerting.
    |
    */

    'security_monitoring' => [
        'failed_login_threshold' => env('AUDIT_FAILED_LOGIN_THRESHOLD', 5),
        'suspicious_activity_threshold' => env('AUDIT_SUSPICIOUS_ACTIVITY_THRESHOLD', 10),
        'bulk_operation_threshold' => env('AUDIT_BULK_OPERATION_THRESHOLD', 50),
        'ip_change_detection' => env('AUDIT_IP_CHANGE_DETECTION', true),
        'alert_email' => env('AUDIT_SECURITY_ALERT_EMAIL', 'security@shopexpressja.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching and performance optimization settings.
    |
    */

    'performance' => [
        'cache_enabled' => env('AUDIT_CACHE_ENABLED', true),
        'cache_ttl' => env('AUDIT_CACHE_TTL', 3600),
        'cache_prefix' => 'audit_',
        'index_optimization' => env('AUDIT_INDEX_OPTIMIZATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure export and reporting functionality.
    |
    */

    'export' => [
        'max_records' => env('AUDIT_MAX_EXPORT_RECORDS', 10000),
        'formats' => ['csv', 'pdf'],
        'include_sensitive_data' => false,
        'max_date_range_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditable Models
    |--------------------------------------------------------------------------
    |
    | Configure which models should be automatically audited and which fields
    | should be excluded from audit logs.
    |
    */

    'auditable_models' => [
        'App\Models\User' => [
            'enabled' => true,
            'excluded_fields' => ['password', 'remember_token', 'email_verified_at'],
            'track_relationships' => true,
        ],
        'App\Models\Package' => [
            'enabled' => true,
            'excluded_fields' => [],
            'track_relationships' => true,
        ],
        'App\Models\ConsolidatedPackage' => [
            'enabled' => true,
            'excluded_fields' => [],
            'track_relationships' => true,
        ],
        'App\Models\Manifest' => [
            'enabled' => true,
            'excluded_fields' => [],
            'track_relationships' => true,
        ],
        'App\Models\CustomerTransaction' => [
            'enabled' => true,
            'excluded_fields' => [],
            'track_relationships' => true,
        ],
        'App\Models\PackageDistribution' => [
            'enabled' => true,
            'excluded_fields' => [],
            'track_relationships' => true,
        ],
        'App\Models\Role' => [
            'enabled' => true,
            'excluded_fields' => [],
            'track_relationships' => true,
        ],
        'App\Models\Office' => [
            'enabled' => true,
            'excluded_fields' => [],
            'track_relationships' => false,
        ],
        'App\Models\BroadcastMessage' => [
            'enabled' => true,
            'excluded_fields' => [],
            'track_relationships' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Types
    |--------------------------------------------------------------------------
    |
    | Define the types of events that can be audited.
    |
    */

    'event_types' => [
        'authentication',
        'authorization',
        'model_created',
        'model_updated',
        'model_deleted',
        'model_restored',
        'business_action',
        'financial_transaction',
        'system_event',
        'security_event',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure how audit-related notifications are sent.
    |
    */

    'notifications' => [
        'security_alerts' => [
            'enabled' => true,
            'channels' => ['mail'],
            'recipients' => [
                env('AUDIT_SECURITY_ALERT_EMAIL', 'security@shopexpressja.com'),
            ],
        ],
        'system_health' => [
            'enabled' => true,
            'channels' => ['mail'],
            'recipients' => [
                env('AUDIT_ADMIN_EMAIL', 'admin@shopexpressja.com'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Integrity
    |--------------------------------------------------------------------------
    |
    | Configure data integrity and security features.
    |
    */

    'data_integrity' => [
        'checksums_enabled' => true,
        'tamper_detection' => true,
        'immutable_logs' => true,
        'encryption_enabled' => false, // Enable if sensitive data needs encryption
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Settings
    |--------------------------------------------------------------------------
    |
    | Configure compliance-specific requirements.
    |
    */

    'compliance' => [
        'mode_enabled' => false,
        'standard' => 'SOX', // SOX, HIPAA, GDPR, etc.
        'extended_retention' => true,
        'audit_trail_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | External Integration
    |--------------------------------------------------------------------------
    |
    | Configure integration with external audit systems or SIEM tools.
    |
    */

    'external_integration' => [
        'enabled' => false,
        'endpoints' => [
            // 'siem_system' => 'https://siem.company.com/api/logs',
            // 'compliance_system' => 'https://compliance.company.com/api/audit',
        ],
        'authentication' => [
            // 'type' => 'bearer_token',
            // 'token' => env('EXTERNAL_AUDIT_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted IP Addresses
    |--------------------------------------------------------------------------
    |
    | Define IP addresses or ranges that are considered trusted and may
    | receive different audit treatment.
    |
    */

    'trusted_ips' => [
        // '192.168.1.0/24',    // Internal network
        // '10.0.0.0/8',        // VPN network
        // '203.0.113.0/24',    // Office network
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to development and testing environments.
    |
    */

    'development' => [
        'fake_data_enabled' => env('APP_ENV') === 'testing',
        'verbose_logging' => env('APP_DEBUG', false),
        'test_mode' => env('APP_ENV') === 'testing',
    ],

];
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where backup files are stored and storage limits.
    |
    */
    'storage' => [
        'path' => env('BACKUP_STORAGE_PATH', storage_path('app/backups')),
        'max_file_size' => env('BACKUP_MAX_FILE_SIZE', 2048), // MB
        'max_storage_size' => env('BACKUP_MAX_STORAGE_SIZE', 10240), // MB (10GB default)
        'disk' => env('BACKUP_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for MySQL database backup operations.
    |
    */
    'database' => [
        'timeout' => env('DB_BACKUP_TIMEOUT', 300), // seconds
        'single_transaction' => env('DB_BACKUP_SINGLE_TRANSACTION', true),
        'routines' => env('DB_BACKUP_ROUTINES', true),
        'triggers' => env('DB_BACKUP_TRIGGERS', true),
        'lock_tables' => env('DB_BACKUP_LOCK_TABLES', false),
        'add_drop_table' => env('DB_BACKUP_ADD_DROP_TABLE', true),
        'extended_insert' => env('DB_BACKUP_EXTENDED_INSERT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for file storage backup operations.
    |
    */
    'files' => [
        'directories' => [
            'storage/app/public/pre-alerts',
            'storage/app/public/receipts',
        ],
        'compression_level' => env('BACKUP_COMPRESSION_LEVEL', 6), // 0-9
        'exclude_patterns' => [
            '*.tmp',
            '*.log',
            '.DS_Store',
            'Thumbs.db',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Policy Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how long backup files are kept before automatic cleanup.
    |
    */
    'retention' => [
        'database_days' => env('BACKUP_DATABASE_RETENTION_DAYS', 30),
        'files_days' => env('BACKUP_FILES_RETENTION_DAYS', 14),
        'cleanup_enabled' => env('BACKUP_CLEANUP_ENABLED', true),
        'min_backups_to_keep' => env('BACKUP_MIN_BACKUPS_TO_KEEP', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure email notifications for backup operations.
    |
    */
    'notifications' => [
        'email' => env('BACKUP_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS')),
        'notify_on_success' => env('BACKUP_NOTIFY_ON_SUCCESS', false),
        'notify_on_failure' => env('BACKUP_NOTIFY_ON_FAILURE', true),
        'notify_on_cleanup' => env('BACKUP_NOTIFY_ON_CLEANUP', false),
        'channels' => ['mail'], // Future: could include 'slack', 'discord', etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for backup monitoring and health checks.
    |
    */
    'monitoring' => [
        'enabled' => env('BACKUP_MONITORING_ENABLED', true),
        'storage_warning_threshold' => env('BACKUP_STORAGE_WARNING_THRESHOLD', 80), // percentage
        'max_backup_age_hours' => env('BACKUP_MAX_AGE_HOURS', 48), // hours
        'health_check_enabled' => env('BACKUP_HEALTH_CHECK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for backup operations and file access.
    |
    */
    'security' => [
        'encrypt_backups' => env('BACKUP_ENCRYPT_BACKUPS', false),
        'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
        'file_permissions' => env('BACKUP_FILE_PERMISSIONS', 0600),
        'directory_permissions' => env('BACKUP_DIRECTORY_PERMISSIONS', 0700),
        'download_link_ttl' => env('BACKUP_DOWNLOAD_LINK_TTL', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for automated backup schedules.
    |
    */
    'schedule' => [
        'default_frequency' => env('BACKUP_DEFAULT_FREQUENCY', 'daily'),
        'default_time' => env('BACKUP_DEFAULT_TIME', '02:00'),
        'max_concurrent_backups' => env('BACKUP_MAX_CONCURRENT', 1),
        'retry_attempts' => env('BACKUP_RETRY_ATTEMPTS', 1),
        'retry_delay' => env('BACKUP_RETRY_DELAY', 300), // seconds
    ],
];
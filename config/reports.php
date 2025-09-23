<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Report System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the business reporting system.
    |
    */

    'enabled' => env('REPORTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('REPORTS_CACHE_ENABLED', true),
        'ttl' => env('REPORTS_CACHE_TTL', 1800), // 30 minutes
        'prefix' => env('REPORTS_CACHE_PREFIX', 'reports'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'max_records_per_page' => env('REPORTS_MAX_RECORDS_PER_PAGE', 100),
        'query_timeout' => env('REPORTS_QUERY_TIMEOUT', 30),
        'export_timeout' => env('REPORTS_EXPORT_TIMEOUT', 300),
        'max_export_records' => env('REPORTS_MAX_EXPORT_RECORDS', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'rate_limit' => env('REPORTS_RATE_LIMIT', 60), // requests per minute
        'require_authentication' => env('REPORTS_REQUIRE_AUTH', true),
        'audit_access' => env('REPORTS_AUDIT_ACCESS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    */
    'exports' => [
        'enabled' => env('REPORTS_EXPORTS_ENABLED', true),
        'formats' => ['csv', 'pdf', 'excel'],
        'storage_disk' => env('REPORTS_EXPORT_DISK', 'local'),
        'cleanup_after_days' => env('REPORTS_EXPORT_CLEANUP_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chart Configuration
    |--------------------------------------------------------------------------
    */
    'charts' => [
        'enabled' => env('REPORTS_CHARTS_ENABLED', true),
        'default_type' => env('REPORTS_DEFAULT_CHART_TYPE', 'collections_overview'),
        'max_data_points' => env('REPORTS_MAX_CHART_POINTS', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */
    'error_handling' => [
        'show_detailed_errors' => env('REPORTS_SHOW_DETAILED_ERRORS', false),
        'log_errors' => env('REPORTS_LOG_ERRORS', true),
        'notify_on_errors' => env('REPORTS_NOTIFY_ON_ERRORS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Report Types
    |--------------------------------------------------------------------------
    */
    'types' => [
        'sales_collections' => [
            'name' => 'Sales & Collections',
            'description' => 'Revenue analysis and outstanding receivables',
            'policy_method' => 'viewSalesReports',
            'cache_ttl' => 900, // 15 minutes
        ],
        'manifest_performance' => [
            'name' => 'Manifest Performance',
            'description' => 'Shipping efficiency and operational metrics',
            'policy_method' => 'viewManifestReports',
            'cache_ttl' => 1800, // 30 minutes
        ],
        'customer_analytics' => [
            'name' => 'Customer Analytics',
            'description' => 'Customer behavior and account analysis',
            'policy_method' => 'viewCustomerReports',
            'cache_ttl' => 3600, // 1 hour
        ],
        'financial_summary' => [
            'name' => 'Financial Summary',
            'description' => 'Comprehensive financial overview',
            'policy_method' => 'viewFinancialReports',
            'cache_ttl' => 900, // 15 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Health Checks
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('REPORTS_MONITORING_ENABLED', true),
        'health_check_interval' => env('REPORTS_HEALTH_CHECK_INTERVAL', 300), // 5 minutes
        'alert_thresholds' => [
            'error_rate' => env('REPORTS_ERROR_RATE_THRESHOLD', 0.05), // 5%
            'response_time' => env('REPORTS_RESPONSE_TIME_THRESHOLD', 5000), // 5 seconds
        ],
    ],
];
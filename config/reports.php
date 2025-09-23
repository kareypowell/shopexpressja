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
        'driver' => env('REPORTS_CACHE_DRIVER', env('CACHE_DRIVER', 'redis')),
        'prefix' => env('REPORTS_CACHE_PREFIX', 'reports'),
        
        'ttl' => [
            'query_results' => env('REPORTS_CACHE_TTL_QUERY', 15), // minutes
            'aggregated_data' => env('REPORTS_CACHE_TTL_AGGREGATED', 60), // minutes
            'report_templates' => env('REPORTS_CACHE_TTL_TEMPLATES', 1440), // 24 hours
            'chart_data' => env('REPORTS_CACHE_TTL_CHARTS', 30), // minutes
            'dashboard_widgets' => env('REPORTS_CACHE_TTL_DASHBOARD', 10), // minutes
            'export_data' => env('REPORTS_CACHE_TTL_EXPORT', 5), // minutes
        ],
        
        'warming' => [
            'enabled' => env('REPORTS_CACHE_WARMING_ENABLED', true),
            'auto_warm_cache' => env('REPORTS_AUTO_WARM_CACHE', true),
            'warmup_delay_minutes' => env('REPORTS_CACHE_WARMUP_DELAY', 2),
            'queue' => env('REPORTS_CACHE_WARMUP_QUEUE', 'reports'),
        ],
        
        'monitoring' => [
            'log_performance' => env('REPORTS_CACHE_LOG_PERFORMANCE', true),
            'slow_query_threshold_ms' => env('REPORTS_CACHE_SLOW_THRESHOLD', 1000),
            'track_hit_rates' => env('REPORTS_CACHE_TRACK_HITS', true),
        ],
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
        
        'query_optimization' => [
            'enabled' => env('REPORTS_QUERY_OPTIMIZATION_ENABLED', true),
            'log_slow_queries' => env('REPORTS_LOG_SLOW_QUERIES', true),
            'slow_query_threshold_ms' => env('REPORTS_SLOW_QUERY_THRESHOLD', 1000),
            'enable_query_caching' => env('REPORTS_ENABLE_QUERY_CACHING', true),
        ],
        
        'database' => [
            'use_read_replica' => env('REPORTS_USE_READ_REPLICA', false),
            'connection' => env('REPORTS_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
            'chunk_size' => env('REPORTS_QUERY_CHUNK_SIZE', 1000),
        ],
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
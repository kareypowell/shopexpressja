<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the audit logging system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Auditable Models
    |--------------------------------------------------------------------------
    |
    | List of models that should be automatically audited by the 
    | UniversalAuditObserver. Add or remove models as needed.
    |
    */
    'auditable_models' => [
        'App\Models\User',
        'App\Models\Package',
        'App\Models\ConsolidatedPackage',
        'App\Models\Manifest',
        'App\Models\CustomerTransaction',
        'App\Models\PackageDistribution',
        'App\Models\Office',
        'App\Models\Address',
        'App\Models\Rate',
        'App\Models\BroadcastMessage',
        'App\Models\Backup',
        'App\Models\Role',
        'App\Models\Profile',
        'App\Models\Shipper',
        'App\Models\PreAlert',
        'App\Models\PackageItem',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should never be included in audit logs for security
    | and privacy reasons.
    |
    */
    'excluded_fields' => [
        'password',
        'remember_token',
        'api_token',
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Models
    |--------------------------------------------------------------------------
    |
    | Models that are considered critical for business operations and
    | should have extended retention periods for their audit logs.
    |
    */
    'critical_models' => [
        'App\Models\User',
        'App\Models\CustomerTransaction',
        'App\Models\PackageDistribution',
        'App\Models\Manifest',
        'App\Models\ConsolidatedPackage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Observer Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the universal audit observer behavior.
    |
    */
    'observer' => [
        // Whether to enable asynchronous audit logging for better performance
        'async_logging' => env('AUDIT_ASYNC_LOGGING', true),
        
        // Whether to log model restorations
        'log_restorations' => true,
        
        // Whether to log force deletions as security events
        'log_force_deletions' => true,
        
        // Maximum number of audit entries to process in a single batch
        'batch_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model-Specific Configurations
    |--------------------------------------------------------------------------
    |
    | Override default behavior for specific models.
    |
    */
    'model_configs' => [
        'App\Models\User' => [
            'excluded_fields' => [
                'password',
                'remember_token',
                'api_token',
                'email_verified_at',
                'last_login_at',
            ],
            'critical' => true,
        ],
        'App\Models\Package' => [
            'excluded_fields' => [],
            'track_status_changes' => true,
        ],
        'App\Models\CustomerTransaction' => [
            'excluded_fields' => [],
            'critical' => true,
            'high_priority' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for integrating with existing observers and systems.
    |
    */
    'integration' => [
        // Whether to integrate with existing model observers
        'preserve_existing_observers' => true,
        
        // Whether to skip audit logging if other observers fail
        'fail_gracefully' => true,
        
        // Whether to log observer integration events
        'log_integration_events' => false,
    ],
];
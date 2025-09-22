<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Package;
use App\Models\Profile;
use App\Models\Manifest;
use App\Models\CustomerTransaction;
use App\Observers\UserObserver;
use App\Observers\PackageObserver;
use App\Observers\ProfileObserver;
use App\Observers\ReportCacheObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register dashboard services
        $this->app->singleton(\App\Services\DashboardCacheService::class);
        $this->app->singleton(\App\Services\DashboardAnalyticsService::class);
        
        // Register audit service
        $this->app->singleton(\App\Services\AuditService::class);
        
        // Register reporting services
        $this->app->singleton(\App\Services\ReportCacheService::class);
        $this->app->singleton(\App\Services\ManifestAnalyticsService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register model observers for cache invalidation
        User::observe(UserObserver::class);
        Package::observe(PackageObserver::class);
        Profile::observe(ProfileObserver::class);
        
        // Register report cache observer for reporting system
        $reportCacheObserver = $this->app->make(ReportCacheObserver::class);
        Package::observe($reportCacheObserver);
        Manifest::observe($reportCacheObserver);
        CustomerTransaction::observe($reportCacheObserver);
        User::observe($reportCacheObserver);
    }
}

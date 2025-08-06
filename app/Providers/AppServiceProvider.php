<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Package;
use App\Models\Profile;
use App\Observers\UserObserver;
use App\Observers\PackageObserver;
use App\Observers\ProfileObserver;
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
    }
}

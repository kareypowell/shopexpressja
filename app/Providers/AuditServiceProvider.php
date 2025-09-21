<?php

namespace App\Providers;

use App\Observers\UniversalAuditObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the UniversalAuditObserver as a singleton
        $this->app->singleton(UniversalAuditObserver::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the universal audit observer with all auditable models
        $this->registerAuditObserver();
    }

    /**
     * Register the universal audit observer with auditable models
     */
    protected function registerAuditObserver(): void
    {
        $auditableModels = Config::get('audit.auditable_models', []);
        $observer = $this->app->make(UniversalAuditObserver::class);

        foreach ($auditableModels as $modelClass) {
            if (class_exists($modelClass)) {
                // Always register the universal audit observer
                // Laravel allows multiple observers per model and they will all be called
                $modelClass::observe($observer);
            }
        }
    }


}
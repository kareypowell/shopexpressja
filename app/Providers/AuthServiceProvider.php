<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Policies\CustomerPolicy;
use App\Models\Package;
use App\Policies\PackagePolicy;
use App\Models\ConsolidatedPackage;
use App\Policies\ConsolidatedPackagePolicy;
use App\Models\BroadcastMessage;
use App\Policies\BroadcastMessagePolicy;
use App\Models\Office;
use App\Policies\OfficePolicy;
use App\Models\Address;
use App\Policies\AddressPolicy;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Package::class => PackagePolicy::class,
        ConsolidatedPackage::class => ConsolidatedPackagePolicy::class,
        BroadcastMessage::class => BroadcastMessagePolicy::class,
        Office::class => OfficePolicy::class,
        Address::class => AddressPolicy::class,
    ];

    /**
     * Additional policy mappings for specific contexts.
     * These are used when we need different authorization logic
     * for the same model in different contexts.
     */
    protected $contextualPolicies = [
        'customer' => CustomerPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        $this->registerCustomerPolicyGates();

        // Define superadmin gate
        Gate::define('super-admin-access', function ($user) {
            return $user->isSuperAdmin();
        });

        // Define admin gate
        Gate::define('admin-access', function ($user) {
            return $user->isAdmin();
        });

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $url);
        });
    }

    /**
     * Register customer-specific policy gates.
     * These gates use the CustomerPolicy for customer-specific operations.
     *
     * @return void
     */
    protected function registerCustomerPolicyGates()
    {
        // Customer management gates
        Gate::define('customer.viewAny', [CustomerPolicy::class, 'viewAny']);
        Gate::define('customer.view', [CustomerPolicy::class, 'view']);
        Gate::define('customer.create', [CustomerPolicy::class, 'create']);
        Gate::define('customer.update', [CustomerPolicy::class, 'update']);
        Gate::define('customer.delete', [CustomerPolicy::class, 'delete']);
        Gate::define('customer.restore', [CustomerPolicy::class, 'restore']);
        Gate::define('customer.forceDelete', [CustomerPolicy::class, 'forceDelete']);

        // Customer-specific operation gates
        Gate::define('customer.viewFinancials', [CustomerPolicy::class, 'viewFinancials']);
        Gate::define('customer.viewPackages', [CustomerPolicy::class, 'viewPackages']);
        Gate::define('customer.bulkOperations', [CustomerPolicy::class, 'bulkOperations']);
        Gate::define('customer.export', [CustomerPolicy::class, 'export']);
        Gate::define('customer.sendEmail', [CustomerPolicy::class, 'sendEmail']);
        Gate::define('customer.viewDeleted', [CustomerPolicy::class, 'viewDeleted']);
    }
}

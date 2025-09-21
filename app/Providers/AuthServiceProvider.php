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
use App\Models\Manifest;
use App\Policies\ManifestPolicy;
use App\Models\Role;
use App\Policies\RolePolicy;
use App\Models\AuditLog;
use App\Policies\AuditLogPolicy;
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
        Manifest::class => ManifestPolicy::class,
        Role::class => RolePolicy::class,
        AuditLog::class => AuditLogPolicy::class,
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
        $this->registerUserPolicyGates();
        $this->registerRolePolicyGates();

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

    /**
     * Register user-specific policy gates.
     * These gates use the UserPolicy for user management operations.
     *
     * @return void
     */
    protected function registerUserPolicyGates()
    {
        // User management gates
        Gate::define('user.viewAny', [UserPolicy::class, 'viewAny']);
        Gate::define('user.view', [UserPolicy::class, 'view']);
        Gate::define('user.create', [UserPolicy::class, 'create']);
        Gate::define('user.update', [UserPolicy::class, 'update']);
        Gate::define('user.delete', [UserPolicy::class, 'delete']);
        Gate::define('user.restore', [UserPolicy::class, 'restore']);
        Gate::define('user.forceDelete', [UserPolicy::class, 'forceDelete']);

        // User role management gates
        Gate::define('user.changeRole', [UserPolicy::class, 'changeRole']);
        Gate::define('user.manageRoles', [UserPolicy::class, 'manageRoles']);
        Gate::define('user.viewStatistics', [UserPolicy::class, 'viewStatistics']);
        Gate::define('user.createWithRole', [UserPolicy::class, 'createWithRole']);
    }

    /**
     * Register role-specific policy gates.
     * These gates use the RolePolicy for role management operations.
     *
     * @return void
     */
    protected function registerRolePolicyGates()
    {
        // Role management gates
        Gate::define('role.viewAny', [RolePolicy::class, 'viewAny']);
        Gate::define('role.view', [RolePolicy::class, 'view']);
        Gate::define('role.create', [RolePolicy::class, 'create']);
        Gate::define('role.update', [RolePolicy::class, 'update']);
        Gate::define('role.delete', [RolePolicy::class, 'delete']);
        Gate::define('role.manageAssignments', [RolePolicy::class, 'manageAssignments']);
        Gate::define('role.viewAuditTrail', [RolePolicy::class, 'viewAuditTrail']);
    }
}

<?php

namespace App\Providers;

use App\Events\RoleChanged;
use App\Listeners\AuthenticationAuditListener;
use App\Listeners\RoleChangeAuditListener;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            AuthenticationAuditListener::class . '@handleRegistered',
        ],
        Login::class => [
            AuthenticationAuditListener::class . '@handleLogin',
        ],
        Logout::class => [
            AuthenticationAuditListener::class . '@handleLogout',
        ],
        Failed::class => [
            AuthenticationAuditListener::class . '@handleFailed',
        ],
        Attempting::class => [
            AuthenticationAuditListener::class . '@handleAttempting',
        ],
        PasswordReset::class => [
            AuthenticationAuditListener::class . '@handlePasswordReset',
        ],
        Verified::class => [
            AuthenticationAuditListener::class . '@handleVerified',
        ],
        RoleChanged::class => [
            RoleChangeAuditListener::class . '@handleRoleChange',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

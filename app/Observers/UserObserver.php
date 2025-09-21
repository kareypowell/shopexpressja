<?php

namespace App\Observers;

use App\Events\RoleChanged;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CustomerCacheInvalidationService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected CustomerCacheInvalidationService $cacheInvalidationService;
    protected AuditService $auditService;

    public function __construct(
        CustomerCacheInvalidationService $cacheInvalidationService,
        AuditService $auditService
    ) {
        $this->cacheInvalidationService = $cacheInvalidationService;
        $this->auditService = $auditService;
    }

    /**
     * Handle the User "created" event.
     *
     * @param User $user
     * @return void
     */
    public function created(User $user)
    {
        // Log user creation to audit system
        $this->auditService->logModelCreated($user);
        
        // Log business action for user registration
        $this->auditService->logBusinessAction('user_registered', $user, [
            'user_type' => $user->role->name ?? 'customer',
            'email' => $user->email,
            'registration_method' => 'manual', // Could be enhanced to track registration source
        ]);

        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerCreation($user);
        }
    }

    /**
     * Handle the User "updating" event to capture role changes.
     *
     * @param User $user
     * @return void
     */
    public function updating(User $user)
    {
        // Check if role_id is being changed
        if ($user->isDirty('role_id')) {
            $oldRoleId = $user->getOriginal('role_id');
            $newRoleId = $user->role_id;
            
            // Store the role change data for the updated event
            $user->_roleChangeData = [
                'old_role_id' => $oldRoleId,
                'new_role_id' => $newRoleId,
            ];
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user)
    {
        // Get the changes that were made
        $changes = $user->getChanges();
        $originalValues = $user->getOriginal();
        
        // Log user update to audit system (excluding role changes as they're handled separately)
        if (!isset($user->_roleChangeData)) {
            $this->auditService->logModelUpdated($user, $originalValues);
        }
        
        // Log specific business actions for important changes
        if (isset($changes['email'])) {
            $this->auditService->logBusinessAction('user_email_changed', $user, [
                'old_email' => $originalValues['email'] ?? null,
                'new_email' => $user->email,
            ]);
        }
        
        if (isset($changes['account_balance'])) {
            $this->auditService->logFinancialTransaction('account_balance_changed', [
                'user_id' => $user->id,
                'old_balance' => $originalValues['account_balance'] ?? 0,
                'new_balance' => $user->account_balance,
                'change_amount' => $user->account_balance - ($originalValues['account_balance'] ?? 0),
                'type' => 'balance_adjustment',
            ], $user);
        }

        // Handle role change if it occurred
        if (isset($user->_roleChangeData)) {
            $roleChangeData = $user->_roleChangeData;
            
            // Fire the role changed event
            event(new RoleChanged(
                $user,
                $roleChangeData['old_role_id'],
                $roleChangeData['new_role_id']
            ));
            
            // Clean up the temporary data
            unset($user->_roleChangeData);
        }

        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerProfileUpdate($user);
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function deleted(User $user)
    {
        // Log user deletion to audit system
        $this->auditService->logModelDeleted($user);
        
        // Log business action for user deactivation
        $this->auditService->logBusinessAction('user_deactivated', $user, [
            'user_type' => $user->role->name ?? 'customer',
            'email' => $user->email,
            'deactivation_method' => 'soft_delete',
        ]);

        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerDeletion($user);
        }
    }

    /**
     * Handle the User "restored" event.
     *
     * @param User $user
     * @return void
     */
    public function restored(User $user)
    {
        // Log user restoration to audit system
        $this->auditService->logBusinessAction('user_restored', $user, [
            'user_type' => $user->role->name ?? 'customer',
            'email' => $user->email,
            'restoration_method' => 'soft_delete_restore',
        ]);

        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerRestoration($user);
        }
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        // Log user force deletion to audit system
        $this->auditService->logBusinessAction('user_permanently_deleted', $user, [
            'user_type' => $user->role->name ?? 'customer',
            'email' => $user->email,
            'deletion_method' => 'force_delete',
        ]);

        // Only handle cache for customers
        if ($user->isCustomer()) {
            $this->cacheInvalidationService->handleCustomerDeletion($user);
        }
    }
}
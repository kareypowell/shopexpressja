<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditService;
use App\Services\RoleChangeAuditService;
use Illuminate\Support\Facades\Log;

class RoleChangeAuditListener
{
    protected $auditService;
    protected $roleChangeAuditService;

    public function __construct(AuditService $auditService, RoleChangeAuditService $roleChangeAuditService)
    {
        $this->auditService = $auditService;
        $this->roleChangeAuditService = $roleChangeAuditService;
    }

    /**
     * Handle role change events
     */
    public function handleRoleChange(\App\Events\RoleChanged $event): void
    {
        try {
            // Log to existing RoleChangeAudit system
            $roleChangeAudit = $this->roleChangeAuditService->logRoleChange(
                $event->user,
                $event->oldRoleId,
                $event->newRoleId,
                $event->reason,
                request()
            );

            // Also log to new unified audit system
            $oldRole = $event->oldRoleId ? \App\Models\Role::find($event->oldRoleId) : null;
            $newRole = \App\Models\Role::find($event->newRoleId);

            $this->auditService->logAuthorization('role_change', $event->user, [
                'old_role_id' => $event->oldRoleId,
                'old_role_name' => $oldRole?->name,
            ], [
                'new_role_id' => $event->newRoleId,
                'new_role_name' => $newRole?->name,
                'reason' => $event->reason,
                'role_change_audit_id' => $roleChangeAudit->id,
            ]);

            // Log security event if this is a privilege escalation
            if ($this->isPrivilegeEscalation($oldRole, $newRole)) {
                $this->auditService->logSecurityEvent('privilege_escalation', [
                    'severity' => 'high',
                    'user_id' => $event->user->id,
                    'user_name' => $event->user->name,
                    'user_email' => $event->user->email,
                    'old_role' => $oldRole?->name ?? 'none',
                    'new_role' => $newRole?->name,
                    'reason' => $event->reason,
                    'role_change_audit_id' => $roleChangeAudit->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to audit role change event', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id,
                'old_role_id' => $event->oldRoleId,
                'new_role_id' => $event->newRoleId,
            ]);
        }
    }

    /**
     * Handle permission grant events
     */
    public function handlePermissionGrant(User $user, string $permission, ?string $context = null): void
    {
        try {
            $this->auditService->logAuthorization('permission_grant', $user, [], [
                'permission' => $permission,
                'context' => $context,
                'granted_by' => auth()->user()?->name ?? 'System',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to audit permission grant event', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'permission' => $permission,
            ]);
        }
    }

    /**
     * Handle permission revoke events
     */
    public function handlePermissionRevoke(User $user, string $permission, ?string $context = null): void
    {
        try {
            $this->auditService->logAuthorization('permission_revoke', $user, [
                'permission' => $permission,
                'context' => $context,
            ], [
                'revoked_by' => auth()->user()?->name ?? 'System',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to audit permission revoke event', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'permission' => $permission,
            ]);
        }
    }

    /**
     * Handle account status changes (activation, deactivation, suspension)
     */
    public function handleAccountStatusChange(User $user, string $oldStatus, string $newStatus, ?string $reason = null): void
    {
        try {
            $this->auditService->logAuthorization('account_status_change', $user, [
                'status' => $oldStatus,
            ], [
                'status' => $newStatus,
                'reason' => $reason,
                'changed_by' => auth()->user()?->name ?? 'System',
            ]);

            // Log as security event if account is being suspended or deactivated
            if (in_array($newStatus, ['suspended', 'deactivated', 'banned'])) {
                $this->auditService->logSecurityEvent('account_restricted', [
                    'severity' => 'high',
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to audit account status change event', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        }
    }

    /**
     * Determine if a role change represents privilege escalation
     */
    protected function isPrivilegeEscalation($oldRole, $newRole): bool
    {
        if (!$oldRole || !$newRole) {
            return false;
        }

        // Define role hierarchy (higher number = more privileges)
        $roleHierarchy = [
            'customer' => 1,
            'admin' => 2,
            'superadmin' => 3,
        ];

        $oldLevel = $roleHierarchy[$oldRole->name] ?? 0;
        $newLevel = $roleHierarchy[$newRole->name] ?? 0;

        return $newLevel > $oldLevel;
    }
}
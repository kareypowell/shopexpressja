<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any audit logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAuditLogs();
    }

    /**
     * Determine whether the user can view the audit log.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->canAccessAuditLogs();
    }

    /**
     * Determine whether the user can create audit logs.
     * Note: Audit logs are typically created by the system, not users.
     */
    public function create(User $user): bool
    {
        return false; // Audit logs should only be created by the system
    }

    /**
     * Determine whether the user can update the audit log.
     * Note: Audit logs should be immutable for integrity.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false; // Audit logs should be immutable
    }

    /**
     * Determine whether the user can delete the audit log.
     * Note: Audit logs should only be deleted through retention policies.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false; // Audit logs should only be deleted through retention policies
    }

    /**
     * Determine whether the user can export audit logs.
     */
    public function export(User $user): bool
    {
        return $user->canAccessAuditLogs();
    }
}
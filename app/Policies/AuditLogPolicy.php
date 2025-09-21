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
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Only superadmins can view audit logs
        return $user->role && $user->role->name === 'superadmin';
    }

    /**
     * Determine whether the user can view the audit log.
     *
     * @param User $user
     * @param AuditLog $auditLog
     * @return bool
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        // Only superadmins can view individual audit logs
        return $user->role && $user->role->name === 'superadmin';
    }

    /**
     * Determine whether the user can export audit logs.
     *
     * @param User $user
     * @return bool
     */
    public function export(User $user): bool
    {
        // Only superadmins can export audit logs
        return $user->role && $user->role->name === 'superadmin';
    }

    /**
     * Determine whether the user can generate compliance reports.
     *
     * @param User $user
     * @return bool
     */
    public function generateComplianceReport(User $user): bool
    {
        // Only superadmins can generate compliance reports
        return $user->role && $user->role->name === 'superadmin';
    }

    /**
     * Determine whether the user can manage audit settings.
     *
     * @param User $user
     * @return bool
     */
    public function manageSettings(User $user): bool
    {
        // Only superadmins can manage audit settings
        return $user->role && $user->role->name === 'superadmin';
    }

    /**
     * Determine whether the user can create export templates.
     *
     * @param User $user
     * @return bool
     */
    public function createExportTemplate(User $user): bool
    {
        // Only superadmins can create export templates
        return $user->role && $user->role->name === 'superadmin';
    }

    /**
     * Determine whether the user can schedule reports.
     *
     * @param User $user
     * @return bool
     */
    public function scheduleReports(User $user): bool
    {
        // Only superadmins can schedule reports
        return $user->role && $user->role->name === 'superadmin';
    }
}
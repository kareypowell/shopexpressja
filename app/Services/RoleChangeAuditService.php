<?php

namespace App\Services;

use App\Models\RoleChangeAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleChangeAuditService
{
    /**
     * Log a role change event.
     *
     * @param User $user The user whose role is being changed
     * @param int|null $oldRoleId The previous role ID (null for new users)
     * @param int $newRoleId The new role ID
     * @param string|null $reason Optional reason for the change
     * @param Request|null $request Request object to extract IP and user agent
     * @param User|null $changedBy User making the change (defaults to authenticated user)
     * @return RoleChangeAudit
     */
    public function logRoleChange(
        User $user,
        ?int $oldRoleId,
        int $newRoleId,
        ?string $reason = null,
        ?Request $request = null,
        ?User $changedBy = null
    ): RoleChangeAudit {
        // Default to authenticated user if no changedBy is provided
        $changedBy = $changedBy ?? Auth::user();
        
        if (!$changedBy) {
            throw new \InvalidArgumentException('No authenticated user found to log role change');
        }

        // Extract IP address and user agent from request
        $ipAddress = null;
        $userAgent = null;
        
        if ($request) {
            $ipAddress = $this->getClientIpAddress($request);
            $userAgent = $request->userAgent();
        }

        return RoleChangeAudit::create([
            'user_id' => $user->id,
            'changed_by_user_id' => $changedBy->id,
            'old_role_id' => $oldRoleId,
            'new_role_id' => $newRoleId,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get audit trail for a specific user.
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAuditTrailForUser(User $user, int $limit = 50)
    {
        return RoleChangeAudit::forUser($user->id)
            ->with(['changedBy', 'oldRole', 'newRole'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent role changes across the system.
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentRoleChanges(int $days = 30, int $limit = 100)
    {
        return RoleChangeAudit::recent($days)
            ->with(['user', 'changedBy', 'oldRole', 'newRole'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get role changes made by a specific user.
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoleChangesByUser(User $user, int $limit = 50)
    {
        return RoleChangeAudit::byUser($user->id)
            ->with(['user', 'oldRole', 'newRole'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get statistics about role changes.
     *
     * @param int $days
     * @return array
     */
    public function getRoleChangeStatistics(int $days = 30): array
    {
        $baseQuery = RoleChangeAudit::recent($days);

        return [
            'total_changes' => $baseQuery->count(),
            'unique_users_affected' => $baseQuery->distinct('user_id')->count('user_id'),
            'unique_changers' => $baseQuery->distinct('changed_by_user_id')->count('changed_by_user_id'),
            'changes_with_reason' => $baseQuery->whereNotNull('reason')->count(),
            'changes_by_day' => $baseQuery
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Extract the real client IP address from the request.
     *
     * @param Request $request
     * @return string|null
     */
    private function getClientIpAddress(Request $request): ?string
    {
        // Check for various headers that might contain the real IP
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipHeaders as $header) {
            $ip = $request->server($header);
            if (!empty($ip) && $ip !== 'unknown') {
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback to request IP
        return $request->ip();
    }
}
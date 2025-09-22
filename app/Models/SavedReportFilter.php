<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedReportFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'report_type',
        'filter_config',
        'is_shared',
        'shared_with_roles'
    ];

    protected $casts = [
        'filter_config' => 'array',
        'is_shared' => 'boolean',
        'shared_with_roles' => 'array'
    ];

    /**
     * Relationship to the user who owns this filter
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get filters accessible by a specific user
     */
    public function scopeAccessibleBy($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere(function ($subQ) use ($user) {
                  $subQ->where('is_shared', true)
                       ->whereJsonContains('shared_with_roles', $user->role_id);
              });
        });
    }

    /**
     * Scope to filter by report type
     */
    public function scopeForReportType($query, string $reportType)
    {
        return $query->where('report_type', $reportType);
    }

    /**
     * Scope to get only shared filters
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Check if this filter is accessible by a specific user
     */
    public function isAccessibleBy(User $user): bool
    {
        // Owner can always access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check if shared and user's role is in the shared roles
        if ($this->is_shared && in_array($user->role_id, $this->shared_with_roles ?? [])) {
            return true;
        }

        return false;
    }

    /**
     * Get the filter configuration with validation
     */
    public function getValidatedConfig(): array
    {
        $config = $this->filter_config ?? [];
        
        // Ensure required fields exist with defaults
        return array_merge([
            'date_range' => 'last_30_days',
            'start_date' => null,
            'end_date' => null,
            'manifest_types' => [],
            'office_ids' => [],
            'customer_ids' => [],
            'status_filters' => []
        ], $config);
    }

    /**
     * Share this filter with specific roles
     */
    public function shareWithRoles(array $roleIds): void
    {
        $this->update([
            'is_shared' => true,
            'shared_with_roles' => array_unique(array_merge($this->shared_with_roles ?? [], $roleIds))
        ]);
    }

    /**
     * Unshare this filter
     */
    public function unshare(): void
    {
        $this->update([
            'is_shared' => false,
            'shared_with_roles' => null
        ]);
    }
}

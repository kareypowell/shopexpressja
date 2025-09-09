<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ManifestAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'manifest_id',
        'user_id', 
        'action',
        'reason',
        'performed_at'
    ];

    protected $casts = [
        'performed_at' => 'datetime'
    ];

    /**
     * Get the manifest that this audit record belongs to
     */
    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class);
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a human-readable label for the action
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'closed' => 'Closed',
            'unlocked' => 'Unlocked',
            'auto_complete' => 'Auto-closed (All Delivered)',
            default => ucfirst($this->action)
        };
    }

    /**
     * Scope to get audits for a specific manifest
     */
    public function scopeForManifest(Builder $query, int $manifestId): Builder
    {
        return $query->where('manifest_id', $manifestId);
    }

    /**
     * Scope to get audits by a specific user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get audits by action type
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get audits within a date range
     */
    public function scopeInDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent audits (last 30 days by default)
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to order by most recent first
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('performed_at', 'desc');
    }

    /**
     * Create an audit log entry
     */
    public static function logAction(int $manifestId, int $userId, string $action, string $reason): self
    {
        return self::create([
            'manifest_id' => $manifestId,
            'user_id' => $userId,
            'action' => $action,
            'reason' => $reason,
            'performed_at' => now()
        ]);
    }

    /**
     * Get audit trail for a manifest with user information
     */
    public static function getManifestAuditTrail(int $manifestId): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('user:id,first_name,last_name,email')
            ->forManifest($manifestId)
            ->latest()
            ->get();
    }

    /**
     * Get summary of actions for a manifest
     */
    public static function getManifestActionSummary(int $manifestId): array
    {
        $audits = self::forManifest($manifestId)->get();
        
        return [
            'total_actions' => $audits->count(),
            'actions_by_type' => $audits->groupBy('action')->map->count(),
            'last_action' => $audits->sortByDesc('performed_at')->first(),
            'unique_users' => $audits->pluck('user_id')->unique()->count()
        ];
    }
}

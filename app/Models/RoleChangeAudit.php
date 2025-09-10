<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleChangeAudit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'changed_by_user_id',
        'old_role_id',
        'new_role_id',
        'reason',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user whose role was changed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who made the role change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Get the old role.
     */
    public function oldRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'old_role_id');
    }

    /**
     * Get the new role.
     */
    public function newRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'new_role_id');
    }

    /**
     * Scope to get audits for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get audits made by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('changed_by_user_id', $userId);
    }

    /**
     * Scope to get recent audits.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'old_status',
        'new_status',
        'changed_by',
        'changed_at',
        'notes',
    ];

    protected $casts = [
        'old_status' => PackageStatus::class,
        'new_status' => PackageStatus::class,
        'changed_at' => 'datetime',
    ];

    /**
     * Get the package that this history entry belongs to
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user who made the status change
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope to get history for a specific package
     */
    public function scopeForPackage($query, $packageId)
    {
        return $query->where('package_id', $packageId);
    }

    /**
     * Scope to get recent history entries
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('changed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get history by status
     */
    public function scopeByStatus($query, PackageStatus $status)
    {
        return $query->where('new_status', $status->value);
    }

    /**
     * Get formatted timestamp for display
     */
    public function getFormattedChangedAtAttribute(): string
    {
        return $this->changed_at->format('M j, Y g:i A');
    }

    /**
     * Get the status change description
     */
    public function getChangeDescriptionAttribute(): string
    {
        $oldLabel = $this->old_status->getLabel();
        $newLabel = $this->new_status->getLabel();
        
        return "Status changed from {$oldLabel} to {$newLabel}";
    }

    /**
     * Get the duration since this status change
     */
    public function getTimeSinceChangeAttribute(): string
    {
        return $this->changed_at->diffForHumans();
    }

    /**
     * Check if this was a status upgrade (forward progression)
     */
    public function isStatusUpgrade(): bool
    {
        $statusOrder = [
            PackageStatus::PENDING->value => 1,
            PackageStatus::PROCESSING->value => 2,
            PackageStatus::SHIPPED->value => 3,
            PackageStatus::CUSTOMS->value => 4,
            PackageStatus::READY->value => 5,
            PackageStatus::DELIVERED->value => 6,
            PackageStatus::DELAYED->value => 0, // Special case - not part of normal progression
        ];

        $oldOrder = $statusOrder[$this->old_status->value] ?? 0;
        $newOrder = $statusOrder[$this->new_status->value] ?? 0;

        return $newOrder > $oldOrder;
    }

    /**
     * Check if this was a status downgrade (backward movement)
     */
    public function isStatusDowngrade(): bool
    {
        return !$this->isStatusUpgrade() && $this->old_status !== $this->new_status;
    }

    /**
     * Get history entries with user information
     */
    public function scopeWithUser($query)
    {
        return $query->with('changedBy:id,name,email');
    }

    /**
     * Get ordered history (most recent first)
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('changed_at', 'desc');
    }
}

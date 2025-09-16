<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestoreLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_id',
        'restored_by',
        'restore_type',
        'status',
        'pre_restore_backup_path',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the backup that was restored.
     */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    /**
     * Get the user who performed the restore.
     */
    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    /**
     * Scope to get completed restores.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed restores.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get restores by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('restore_type', $type);
    }

    /**
     * Scope to get recent restores.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if restore is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if restore failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if restore is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get the duration of the restore operation.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->duration;
        
        if (!$duration) {
            return 'N/A';
        }

        if ($duration < 60) {
            return $duration . ' seconds';
        } elseif ($duration < 3600) {
            return round($duration / 60, 1) . ' minutes';
        } else {
            return round($duration / 3600, 1) . ' hours';
        }
    }

    /**
     * Get the restore type in human readable format.
     */
    public function getRestoreTypeLabelAttribute(): string
    {
        return match($this->restore_type) {
            'database' => 'Database Only',
            'files' => 'Files Only',
            'full' => 'Full Restore',
            default => ucfirst($this->restore_type)
        };
    }

    /**
     * Mark the restore as started.
     */
    public function markAsStarted(): void
    {
        $this->started_at = now();
        $this->status = 'pending';
        $this->save();
    }

    /**
     * Mark the restore as completed.
     */
    public function markAsCompleted(): void
    {
        $this->completed_at = now();
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Mark the restore as failed.
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->completed_at = now();
        $this->status = 'failed';
        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }
        $this->save();
    }
}

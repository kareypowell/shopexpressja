<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'file_path',
        'file_size',
        'status',
        'created_by',
        'metadata',
        'checksum',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the user who created this backup.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the restore logs for this backup.
     */
    public function restoreLogs(): HasMany
    {
        return $this->hasMany(RestoreLog::class);
    }

    /**
     * Scope to get completed backups.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed backups.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get backups by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if backup is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if backup failed.
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Check if backup is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }
}

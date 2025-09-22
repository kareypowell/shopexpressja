<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ReportExportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_type',
        'export_format',
        'filters',
        'status',
        'file_path',
        'error_message',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'filters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Export statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Export formats
     */
    const FORMAT_PDF = 'pdf';
    const FORMAT_CSV = 'csv';

    /**
     * Get all available statuses
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed'
        ];
    }

    /**
     * Get all available export formats
     */
    public static function getAvailableFormats(): array
    {
        return [
            self::FORMAT_PDF => 'PDF',
            self::FORMAT_CSV => 'CSV'
        ];
    }

    /**
     * Relationship to the user who requested this export
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get jobs by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get processing jobs
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope to get completed jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get failed jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get jobs for a specific user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Mark job as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now()
        ]);
    }

    /**
     * Mark job as completed
     */
    public function markAsCompleted(string $filePath): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'completed_at' => now(),
            'error_message' => null
        ]);
    }

    /**
     * Mark job as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now()
        ]);
    }

    /**
     * Check if the job is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the job has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the job is still processing
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Get the processing duration in seconds
     */
    public function getProcessingDuration(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get a human-readable status description
     */
    public function getStatusDescription(): string
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Waiting to be processed';
            case self::STATUS_PROCESSING:
                return 'Currently processing';
            case self::STATUS_COMPLETED:
                return 'Export completed successfully';
            case self::STATUS_FAILED:
                return 'Export failed: ' . ($this->error_message ?? 'Unknown error');
            default:
                return 'Unknown status';
        }
    }

    /**
     * Check if the export file exists and is accessible
     */
    public function hasValidFile(): bool
    {
        return $this->isCompleted() && 
               $this->file_path && 
               file_exists(storage_path('app/' . $this->file_path));
    }

    /**
     * Get the download URL for the export file
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->hasValidFile()) {
            return null;
        }

        return route('reports.export.download', ['job' => $this->id]);
    }
}

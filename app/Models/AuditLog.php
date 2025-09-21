<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'additional_data',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'additional_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by auditable model type.
     */
    public function scopeAuditableType($query, string $type)
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Scope to filter by IP address.
     */
    public function scopeByIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope to get recent audit logs.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Get formatted old values for display.
     */
    public function getFormattedOldValuesAttribute(): ?string
    {
        if (empty($this->old_values)) {
            return null;
        }

        return json_encode($this->old_values, JSON_PRETTY_PRINT);
    }

    /**
     * Get formatted new values for display.
     */
    public function getFormattedNewValuesAttribute(): ?string
    {
        if (empty($this->new_values)) {
            return null;
        }

        return json_encode($this->new_values, JSON_PRETTY_PRINT);
    }

    /**
     * Get the display name for the event type.
     */
    public function getEventTypeDisplayAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->event_type));
    }

    /**
     * Get the display name for the action.
     */
    public function getActionDisplayAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->action));
    }

    /**
     * Get the model name from auditable_type.
     */
    public function getModelNameAttribute(): ?string
    {
        if (!$this->auditable_type) {
            return null;
        }

        return class_basename($this->auditable_type);
    }

    /**
     * Create a new audit log entry.
     */
    public static function createEntry(array $data): self
    {
        return static::create([
            'user_id' => $data['user_id'] ?? auth()->id(),
            'event_type' => $data['event_type'],
            'auditable_type' => $data['auditable_type'] ?? null,
            'auditable_id' => $data['auditable_id'] ?? null,
            'action' => $data['action'],
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'url' => $data['url'] ?? request()->fullUrl(),
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'additional_data' => $data['additional_data'] ?? null,
        ]);
    }

    /**
     * Get changes between old and new values.
     */
    public function getChanges(): array
    {
        if (empty($this->old_values) || empty($this->new_values)) {
            return [];
        }

        $changes = [];
        $oldValues = $this->old_values;
        $newValues = $this->new_values;

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}

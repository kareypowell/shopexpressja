<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'broadcast_message_id',
        'customer_id',
        'email',
        'status',
        'sent_at',
        'failed_at',
        'error_message'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_BOUNCED = 'bounced';

    /**
     * Get the broadcast message this delivery belongs to.
     */
    public function broadcastMessage(): BelongsTo
    {
        return $this->belongsTo(BroadcastMessage::class);
    }

    /**
     * Get the customer this delivery is for.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Scope to get pending deliveries.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get sent deliveries.
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope to get failed deliveries.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get bounced deliveries.
     */
    public function scopeBounced($query)
    {
        return $query->where('status', self::STATUS_BOUNCED);
    }

    /**
     * Mark the delivery as sent.
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'failed_at' => null,
            'error_message' => null
        ]);
    }

    /**
     * Mark the delivery as failed.
     */
    public function markAsFailed(string $errorMessage = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Mark the delivery as bounced.
     */
    public function markAsBounced(string $errorMessage = null): bool
    {
        return $this->update([
            'status' => self::STATUS_BOUNCED,
            'failed_at' => now(),
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Check if the delivery is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the delivery was sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if the delivery failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the delivery bounced.
     */
    public function isBounced(): bool
    {
        return $this->status === self::STATUS_BOUNCED;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'content',
        'sender_id',
        'recipient_type',
        'recipient_count',
        'status',
        'scheduled_at',
        'sent_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    // Recipient type constants
    const RECIPIENT_TYPE_ALL = 'all';
    const RECIPIENT_TYPE_SELECTED = 'selected';

    /**
     * Get the user who sent this broadcast message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the selected recipients for this broadcast message.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class);
    }

    /**
     * Get the delivery records for this broadcast message.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(BroadcastDelivery::class);
    }

    /**
     * Scope to get draft messages.
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to get scheduled messages.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope to get sent messages.
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope to get messages due for sending.
     */
    public function scopeDueForSending($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Get all recipient email addresses for this broadcast.
     */
    public function getRecipientEmails(): array
    {
        if ($this->recipient_type === self::RECIPIENT_TYPE_ALL) {
            return User::customers()->pluck('email')->toArray();
        }

        return $this->recipients()
                   ->with('customer')
                   ->get()
                   ->pluck('customer.email')
                   ->toArray();
    }

    /**
     * Mark the broadcast as sending.
     */
    public function markAsSending(): bool
    {
        return $this->update(['status' => self::STATUS_SENDING]);
    }

    /**
     * Mark the broadcast as sent.
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now()
        ]);
    }

    /**
     * Mark the broadcast as failed.
     */
    public function markAsFailed(): bool
    {
        return $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Check if the broadcast is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the broadcast is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if the broadcast has been sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if the broadcast failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}

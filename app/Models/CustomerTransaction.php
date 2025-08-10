<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'created_by',
        'metadata',
        'flagged_for_review',
        'review_reason',
        'flagged_at',
        'admin_notified',
        'admin_notified_at',
        'review_resolved',
        'admin_response',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'flagged_for_review' => 'boolean',
        'flagged_at' => 'datetime',
        'admin_notified' => 'boolean',
        'admin_notified_at' => 'datetime',
        'review_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // Transaction types
    const TYPE_PAYMENT = 'payment';
    const TYPE_CHARGE = 'charge';
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';
    const TYPE_DISTRIBUTION = 'distribution';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_WRITE_OFF = 'write_off';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getFormattedBalanceBeforeAttribute()
    {
        return number_format($this->balance_before, 2);
    }

    public function getFormattedBalanceAfterAttribute()
    {
        return number_format($this->balance_after, 2);
    }

    public function isCredit()
    {
        return in_array($this->type, [self::TYPE_PAYMENT, self::TYPE_CREDIT, self::TYPE_WRITE_OFF]);
    }

    public function isDebit()
    {
        return in_array($this->type, [self::TYPE_CHARGE, self::TYPE_DEBIT, self::TYPE_DISTRIBUTION]);
    }

    /**
     * Flag transaction for review
     */
    public function flagForReview(string $reason): bool
    {
        return $this->update([
            'flagged_for_review' => true,
            'review_reason' => $reason,
            'flagged_at' => now(),
            'admin_notified' => false,
        ]);
    }

    /**
     * Mark admin as notified
     */
    public function markAdminNotified(): bool
    {
        return $this->update([
            'admin_notified' => true,
            'admin_notified_at' => now(),
        ]);
    }

    /**
     * Resolve the review
     */
    public function resolveReview(string $adminResponse, $resolvedBy): bool
    {
        return $this->update([
            'review_resolved' => true,
            'admin_response' => $adminResponse,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);
    }

    /**
     * Check if transaction is flagged for review
     */
    public function isFlaggedForReview(): bool
    {
        return $this->flagged_for_review && !$this->review_resolved;
    }

    /**
     * Get the user who resolved the review
     */
    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
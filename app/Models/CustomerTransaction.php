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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Transaction types
    const TYPE_PAYMENT = 'payment';
    const TYPE_CHARGE = 'charge';
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';
    const TYPE_DISTRIBUTION = 'distribution';
    const TYPE_ADJUSTMENT = 'adjustment';

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
        return in_array($this->type, [self::TYPE_PAYMENT, self::TYPE_CREDIT]);
    }

    public function isDebit()
    {
        return in_array($this->type, [self::TYPE_CHARGE, self::TYPE_DEBIT, self::TYPE_DISTRIBUTION]);
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
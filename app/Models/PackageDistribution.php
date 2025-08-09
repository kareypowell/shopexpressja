<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'customer_id',
        'distributed_by',
        'distributed_at',
        'total_amount',
        'amount_collected',
        'credit_applied',
        'payment_status',
        'receipt_path',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'distributed_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'amount_collected' => 'decimal:2',
        'credit_applied' => 'decimal:2',
        'email_sent' => 'boolean',
    ];

    /**
     * Get the customer who received the distribution
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the user who distributed the packages
     */
    public function distributedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    /**
     * Get the distribution items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PackageDistributionItem::class, 'distribution_id');
    }

    /**
     * Get the packages included in this distribution
     */
    public function packages()
    {
        return $this->hasManyThrough(
            Package::class,
            PackageDistributionItem::class,
            'distribution_id',
            'id',
            'id',
            'package_id'
        );
    }

    /**
     * Calculate the payment status based on amounts
     */
    public function calculatePaymentStatus(): string
    {
        $creditApplied = $this->credit_applied ? $this->credit_applied : 0;
        $totalReceived = $this->amount_collected + $creditApplied;
        
        if ($totalReceived >= $this->total_amount) {
            return 'paid';
        } elseif ($totalReceived > 0) {
            return 'partial';
        } else {
            return 'unpaid';
        }
    }

    /**
     * Get the outstanding balance
     */
    public function getOutstandingBalanceAttribute(): float
    {
        $creditApplied = $this->credit_applied ? $this->credit_applied : 0;
        $totalReceived = $this->amount_collected + $creditApplied;
        return max(0, $this->total_amount - $totalReceived);
    }

    /**
     * Get the total amount received (cash + credit)
     */
    public function getTotalReceivedAttribute(): float
    {
        $creditApplied = $this->credit_applied ? $this->credit_applied : 0;
        return $this->amount_collected + $creditApplied;
    }

    /**
     * Check if the distribution is fully paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if the distribution is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    /**
     * Check if the distribution is unpaid
     */
    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    /**
     * Generate a unique receipt number
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = 'RCP';
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        return $prefix . $timestamp . $random;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        'manifest_id',
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

    /**
     * Get the manifest if this transaction is linked to one
     */
    public function manifest()
    {
        // Use direct manifest_id column if available, otherwise fall back to reference fields
        if (Schema::hasColumn('customer_transactions', 'manifest_id')) {
            return $this->belongsTo(Manifest::class, 'manifest_id');
        }
        
        return $this->belongsTo(Manifest::class, 'reference_id')
            ->where('reference_type', 'App\\Models\\Manifest');
    }

    /**
     * Get the package if this transaction is linked to one
     */
    public function package()
    {
        return $this->belongsTo(Package::class, 'reference_id')
            ->where('reference_type', 'App\\Models\\Package');
    }

    /**
     * Get the package distribution if this transaction is linked to one
     */
    public function packageDistribution()
    {
        return $this->belongsTo(PackageDistribution::class, 'reference_id')
            ->where('reference_type', 'App\\Models\\PackageDistribution');
    }

    /**
     * Link this transaction to a manifest
     */
    public function linkToManifest(Manifest $manifest): bool
    {
        $updateData = [
            'reference_type' => 'App\\Models\\Manifest',
            'reference_id' => $manifest->id,
        ];
        
        // Also update direct manifest_id column if it exists
        if (Schema::hasColumn('customer_transactions', 'manifest_id')) {
            $updateData['manifest_id'] = $manifest->id;
        }
        
        return $this->update($updateData);
    }

    /**
     * Link this transaction to a package
     */
    public function linkToPackage(Package $package): bool
    {
        return $this->update([
            'reference_type' => 'App\\Models\\Package',
            'reference_id' => $package->id,
        ]);
    }

    /**
     * Link this transaction to a package distribution
     */
    public function linkToPackageDistribution(PackageDistribution $distribution): bool
    {
        return $this->update([
            'reference_type' => 'App\\Models\\PackageDistribution',
            'reference_id' => $distribution->id,
        ]);
    }

    /**
     * Check if transaction is linked to a manifest
     */
    public function isLinkedToManifest(): bool
    {
        return $this->reference_type === 'App\\Models\\Manifest' && $this->reference_id;
    }

    /**
     * Check if transaction is linked to a package
     */
    public function isLinkedToPackage(): bool
    {
        return $this->reference_type === 'App\\Models\\Package' && $this->reference_id;
    }

    /**
     * Check if transaction is linked to a package distribution
     */
    public function isLinkedToPackageDistribution(): bool
    {
        return $this->reference_type === 'App\\Models\\PackageDistribution' && $this->reference_id;
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

    /**
     * Scope to filter transactions by manifest
     */
    public function scopeForManifest($query, $manifestId)
    {
        // Use direct manifest_id column if available for better performance
        if (Schema::hasColumn('customer_transactions', 'manifest_id')) {
            return $query->where('manifest_id', $manifestId);
        }
        
        return $query->where('reference_type', 'App\\Models\\Manifest')
                    ->where('reference_id', $manifestId);
    }

    /**
     * Scope to filter transactions by package
     */
    public function scopeForPackage($query, $packageId)
    {
        return $query->where('reference_type', 'App\\Models\\Package')
                    ->where('reference_id', $packageId);
    }

    /**
     * Scope to filter transactions by package distribution
     */
    public function scopeForPackageDistribution($query, $distributionId)
    {
        return $query->where('reference_type', 'App\\Models\\PackageDistribution')
                    ->where('reference_id', $distributionId);
    }

    /**
     * Scope to get transactions linked to manifests
     */
    public function scopeLinkedToManifests($query)
    {
        // Use direct manifest_id column if available for better performance
        if (Schema::hasColumn('customer_transactions', 'manifest_id')) {
            return $query->whereNotNull('manifest_id');
        }
        
        return $query->where('reference_type', 'App\\Models\\Manifest')
                    ->whereNotNull('reference_id');
    }

    /**
     * Scope to get transactions linked to packages
     */
    public function scopeLinkedToPackages($query)
    {
        return $query->where('reference_type', 'App\\Models\\Package')
                    ->whereNotNull('reference_id');
    }

    /**
     * Scope to get transactions linked to package distributions
     */
    public function scopeLinkedToPackageDistributions($query)
    {
        return $query->where('reference_type', 'App\\Models\\PackageDistribution')
                    ->whereNotNull('reference_id');
    }
}
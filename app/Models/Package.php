<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'manifest_id',
        'shipper_id',
        'office_id',
        'warehouse_receipt_no',
        'tracking_number',
        'description',
        'weight',
        'status',
        'estimated_value',
        'freight_price',
        'customs_duty',
        'storage_fee',
        'delivery_fee',
        'container_type',
        'length_inches',
        'width_inches',
        'height_inches',
        'cubic_feet',
        'consolidated_package_id',
        'is_consolidated',
        'consolidated_at'
    ];

    protected $casts = [
        'cubic_feet' => 'decimal:3',
        'weight' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'length_inches' => 'decimal:2',
        'width_inches' => 'decimal:2',
        'height_inches' => 'decimal:2',
        'is_consolidated' => 'boolean',
        'consolidated_at' => 'datetime'
    ];

    public function scopeSearch($query, $term)
    {
        return $query->where(function($query) use ($term) {
            $query->where('tracking_number', 'like', '%' . $term . '%')
                  ->orWhere('status', 'like', '%' . $term . '%');
            
            // Also search by status labels
            $searchTerm = strtolower($term);
            foreach (PackageStatus::cases() as $status) {
                if (str_contains(strtolower($status->getLabel()), $searchTerm)) {
                    $query->orWhere('status', $status->value);
                }
            }
        });
    }

    public function manifest()
    {
        return $this->belongsTo(Manifest::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shipper()
    {
        return $this->belongsTo(Shipper::class);
    }

    public function packagePreAlert()
    {
        return $this->hasOne(PackagePreAlert::class);
    }

    public function items()
    {
        return $this->hasMany(PackageItem::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(PackageStatusHistory::class);
    }

    /**
     * Get status attribute as PackageStatus instance
     */
    public function getStatusAttribute($value)
    {
        if (!$value) {
            return PackageStatus::PENDING();
        }
        
        try {
            return PackageStatus::from($value);
        } catch (\Exception $e) {
            try {
                return PackageStatus::fromLegacyStatus($value);
            } catch (\Exception $e) {
                \Log::warning('Invalid package status in database, defaulting to pending', [
                    'status_value' => $value,
                    'package_id' => $this->id ?? 'unknown',
                ]);
                return PackageStatus::PENDING();
            }
        }
    }

    /**
     * Set status attribute with validation
     */
    public function setStatusAttribute($value)
    {
        // If it's already a PackageStatus instance, use its value
        if ($value instanceof PackageStatus) {
            $this->attributes['status'] = $value->value;
            return;
        }

        // If it's a string, validate it's a valid status
        if (is_string($value)) {
            // Try to create PackageStatus from the value to validate it
            try {
                $status = PackageStatus::from($value);
                $this->attributes['status'] = $status->value;
            } catch (\Exception $e) {
                // If invalid, try to normalize from legacy status
                try {
                    $status = PackageStatus::fromLegacyStatus($value);
                    $this->attributes['status'] = $status->value;
                } catch (\Exception $e) {
                    // If still invalid, default to pending and log warning
                    \Log::warning('Invalid package status provided, defaulting to pending', [
                        'provided_status' => $value,
                        'package_id' => $this->id ?? 'new',
                    ]);
                    $this->attributes['status'] = PackageStatus::PENDING;
                }
            }
        }
    }

    public function getFormattedWeightAttribute()
    {
        return number_format($this->weight, 2);
    }



    /**
     * Calculate cubic feet from dimensions
     * Formula: (length × width × height) ÷ 1728
     */
    public function calculateCubicFeet(): float
    {
        if ($this->length_inches && $this->width_inches && $this->height_inches) {
            return round(($this->length_inches * $this->width_inches * $this->height_inches) / 1728, 3);
        }
        return 0;
    }

    /**
     * Determine if package belongs to a sea manifest
     */
    public function isSeaPackage(): bool
    {
        return $this->manifest && $this->manifest->type === 'sea';
    }

    /**
     * Calculate total cost for the package
     */
    public function getTotalCostAttribute(): float
    {
        return ($this->freight_price ?? 0) + 
               ($this->customs_duty ?? 0) + 
               ($this->storage_fee ?? 0) + 
               ($this->delivery_fee ?? 0);
    }

    /**
     * Get cost breakdown for the package
     */
    public function getCostBreakdownAttribute(): array
    {
        return [
            'freight' => $this->freight_price ?? 0,
            'customs' => $this->customs_duty ?? 0,
            'storage' => $this->storage_fee ?? 0,
            'delivery' => $this->delivery_fee ?? 0,
            'total' => $this->total_cost,
        ];
    }

    /**
     * Get formatted dimensions string
     */
    public function getFormattedDimensionsAttribute(): string
    {
        if ($this->length_inches && $this->width_inches && $this->height_inches) {
            return "{$this->length_inches}\" × {$this->width_inches}\" × {$this->height_inches}\"";
        }
        return '-';
    }

    /**
     * Get status badge class using PackageStatus class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        try {
            return $this->status->getBadgeClass();
        } catch (\Exception $e) {
            \Log::warning('Failed to get status badge class for package', [
                'package_id' => $this->id,
                'status_value' => $this->attributes['status'] ?? 'null',
                'error' => $e->getMessage()
            ]);
            return 'default';
        }
    }

    /**
     * Get status label using PackageStatus class
     */
    public function getStatusLabelAttribute(): string
    {
        try {
            return $this->status->getLabel();
        } catch (\Exception $e) {
            \Log::warning('Failed to get status label for package', [
                'package_id' => $this->id,
                'status_value' => $this->attributes['status'] ?? 'null',
                'error' => $e->getMessage()
            ]);
            return ucfirst($this->attributes['status'] ?? 'pending');
        }
    }

    /**
     * Get the status value safely
     */
    public function getStatusValueAttribute(): string
    {
        try {
            return $this->status->value;
        } catch (\Exception $e) {
            \Log::warning('Failed to get status value for package', [
                'package_id' => $this->id,
                'status_value' => $this->attributes['status'] ?? 'null',
                'error' => $e->getMessage()
            ]);
            return $this->attributes['status'] ?? 'pending';
        } catch (\Exception $e) {
            \Log::warning('Failed to get status value for package', [
                'package_id' => $this->id,
                'status_value' => $this->attributes['status'] ?? 'null',
                'error' => $e->getMessage()
            ]);
            return 'pending';
        }
    }

    /**
     * Check if package can be distributed
     */
    public function canBeDistributed(): bool
    {
        try {
            return $this->status->allowsDistribution();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get valid status transitions for this package
     */
    public function getValidStatusTransitions(): array
    {
        return $this->status->getValidTransitions();
    }

    /**
     * Check if package can transition to a specific status
     */
    public function canTransitionTo(PackageStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    /**
     * Scope to filter packages by status
     */
    public function scopeByStatus($query, PackageStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to get packages ready for distribution
     */
    public function scopeReadyForDistribution($query)
    {
        return $query->where('status', PackageStatus::READY);
    }

    /**
     * Scope to get packages in transit (shipped or customs)
     */
    public function scopeInTransit($query)
    {
        return $query->whereIn('status', [
            PackageStatus::SHIPPED,
            PackageStatus::CUSTOMS,
        ]);
    }

    /**
     * Scope to get delayed packages
     */
    public function scopeDelayed($query)
    {
        return $query->where('status', PackageStatus::DELAYED);
    }

    /**
     * Scope to get delivered packages
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', PackageStatus::DELIVERED);
    }
}

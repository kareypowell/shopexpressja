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
        'cubic_feet'
    ];

    protected $casts = [
        'status' => PackageStatus::class,
        'cubic_feet' => 'decimal:3',
        'weight' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'length_inches' => 'decimal:2',
        'width_inches' => 'decimal:2',
        'height_inches' => 'decimal:2'
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
     * Set status attribute with validation
     */
    public function setStatusAttribute($value)
    {
        // If it's already a PackageStatus enum, use its value
        if ($value instanceof PackageStatus) {
            $this->attributes['status'] = $value->value;
            return;
        }

        // If it's a string, validate it's a valid status
        if (is_string($value)) {
            // Try to create enum from the value to validate it
            try {
                $status = PackageStatus::from($value);
                $this->attributes['status'] = $status->value;
            } catch (\ValueError $e) {
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
                    $this->attributes['status'] = PackageStatus::PENDING->value;
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
     * Get status badge class using enum method
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status->getBadgeClass();
    }

    /**
     * Get status label using enum method
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->getLabel();
    }

    /**
     * Check if package can be distributed
     */
    public function canBeDistributed(): bool
    {
        return $this->status->allowsDistribution();
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
        return $query->where('status', PackageStatus::READY->value);
    }

    /**
     * Scope to get packages in transit (shipped or customs)
     */
    public function scopeInTransit($query)
    {
        return $query->whereIn('status', [
            PackageStatus::SHIPPED->value,
            PackageStatus::CUSTOMS->value,
        ]);
    }

    /**
     * Scope to get delayed packages
     */
    public function scopeDelayed($query)
    {
        return $query->where('status', PackageStatus::DELAYED->value);
    }

    /**
     * Scope to get delivered packages
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', PackageStatus::DELIVERED->value);
    }
}

<?php

namespace App\Models;

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
        'cubic_feet' => 'decimal:3',
        'weight' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'length_inches' => 'decimal:2',
        'width_inches' => 'decimal:2',
        'height_inches' => 'decimal:2'
    ];

    public function scopeSearch($query, $term)
    {
        return $query->where(
            fn($query) => $query->where('tracking_number', 'like', '%' . $term . '%')
                ->orWhere('status', 'like', '%' . $term . '%')
        );
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
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        switch($this->status) {
            case 'processing':
                return 'primary';
            case 'shipped':
                return 'shs';
            case 'delayed':
                return 'warning';
            case 'ready_for_pickup':
                return 'success';
            default:
                return 'default';
        }
    }
}

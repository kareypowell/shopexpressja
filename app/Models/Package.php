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
        'value',
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
        'value' => 'decimal:2',
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

    public function getFormattedValueAttribute()
    {
        return number_format($this->value, 2);
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
}

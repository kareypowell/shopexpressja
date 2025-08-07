<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageDistributionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_id',
        'package_id',
        'freight_price',
        'customs_duty',
        'storage_fee',
        'delivery_fee',
        'total_cost',
    ];

    protected $casts = [
        'freight_price' => 'decimal:2',
        'customs_duty' => 'decimal:2',
        'storage_fee' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the distribution this item belongs to
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(PackageDistribution::class, 'distribution_id');
    }

    /**
     * Get the package for this distribution item
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Calculate the total cost from individual components
     */
    public function calculateTotalCost(): float
    {
        return $this->freight_price + $this->customs_duty + $this->storage_fee + $this->delivery_fee;
    }

    /**
     * Update the total cost based on individual components
     */
    public function updateTotalCost(): void
    {
        $this->total_cost = $this->calculateTotalCost();
    }

    /**
     * Boot method to automatically calculate total cost
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->updateTotalCost();
        });
    }
}

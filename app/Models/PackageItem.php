<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'description',
        'quantity',
        'weight_per_item'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight_per_item' => 'decimal:2'
    ];

    /**
     * Get the package that owns the item
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Calculate total weight for this item (quantity × weight_per_item)
     */
    public function getTotalWeightAttribute(): float
    {
        return $this->quantity * ($this->weight_per_item ?? 0);
    }
}
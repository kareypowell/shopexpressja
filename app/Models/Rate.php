<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;

    protected $fillable = [
        'weight',
        'min_cubic_feet',
        'max_cubic_feet',
        'price',
        'processing_fee',
        'type'
    ];

    public function scopeSearch($query, $term)
    {
        return $query->where(
            fn($query) => $query->where('weight', 'like', '%' . $term . '%')
                ->orWhere('price', 'like', '%' . $term . '%')
                ->orWhere('type', 'like', '%' . $term . '%')
        );
    }

    /**
     * Scope for finding sea rates based on cubic feet range
     */
    public function scopeForSeaShipment($query, $cubicFeet)
    {
        return $query->where('type', 'sea')
                    ->where('min_cubic_feet', '<=', $cubicFeet)
                    ->where('max_cubic_feet', '>=', $cubicFeet);
    }

    /**
     * Scope for finding air rates based on weight
     */
    public function scopeForAirShipment($query, $weight)
    {
        return $query->where('type', 'air')
                    ->where('weight', $weight);
    }
}

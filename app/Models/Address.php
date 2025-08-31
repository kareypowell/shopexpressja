<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'street_address',
        'city',
        'state',
        'zip_code',
        'country',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Boot method to ensure single primary address constraint
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($address) {
            if ($address->is_primary) {
                // Set all other addresses to not primary
                static::where('is_primary', true)
                      ->where('id', '!=', $address->id)
                      ->update(['is_primary' => false]);
            }
        });
    }

    /**
     * Search scope across all address fields
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('street_address', 'like', "%{$term}%")
                     ->orWhere('city', 'like', "%{$term}%")
                     ->orWhere('state', 'like', "%{$term}%")
                     ->orWhere('zip_code', 'like', "%{$term}%")
                     ->orWhere('country', 'like', "%{$term}%");
    }
}

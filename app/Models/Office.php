<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
    ];

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class, 'pickup_location');
    }

    /**
     * Get manifests through packages relationship
     */
    public function manifests()
    {
        return $this->hasManyThrough(Manifest::class, Package::class, 'office_id', 'id', 'id', 'manifest_id')
                    ->distinct();
    }

    /**
     * Search scope for name and address fields
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                     ->orWhere('address', 'like', "%{$term}%");
    }

    /**
     * Get the count of associated manifests
     */
    public function getManifestCountAttribute()
    {
        return $this->packages()
                    ->distinct('manifest_id')
                    ->count('manifest_id');
    }

    /**
     * Get the count of associated packages
     */
    public function getPackageCountAttribute()
    {
        return $this->packages()->count();
    }

    /**
     * Get the count of associated profiles
     */
    public function getProfileCountAttribute()
    {
        return $this->profiles()->count();
    }
}

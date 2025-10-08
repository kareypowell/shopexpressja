<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreAlert extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function scopeSearch($query, $term)
    {
        return $query->where(function($query) use ($term) {
            $query->where('pre_alerts.tracking_number', 'like', '%' . $term . '%')
                ->orWhere('pre_alerts.description', 'like', '%' . $term . '%')
                ->orWhere('pre_alerts.value', 'like', '%' . $term . '%');
            
            // Only search user fields if users table is joined
            $joins = collect($query->getQuery()->joins ?? []);
            if ($joins->contains(fn($join) => $join->table === 'users')) {
                $query->orWhere('users.first_name', 'like', '%' . $term . '%')
                      ->orWhere('users.last_name', 'like', '%' . $term . '%');
            }
            
            // Only search profile fields if profiles table is joined
            if ($joins->contains(fn($join) => $join->table === 'profiles')) {
                $query->orWhere('profiles.account_number', 'like', '%' . $term . '%');
            }
            
            // Always search shipper via relationship
            $query->orWhereHas('shipper', function($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%');
            });
        });
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

    public function getFormattedValueAttribute()
    {
        return number_format($this->value, 2);
    }

    /**
     * Set tracking number attribute with automatic uppercase conversion
     */
    public function setTrackingNumberAttribute($value)
    {
        // Convert tracking number to uppercase and trim whitespace
        $this->attributes['tracking_number'] = $value ? strtoupper(trim($value)) : $value;
    }
}

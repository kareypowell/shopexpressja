<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

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

    public function getFormattedWeightAttribute()
    {
        return number_format($this->weight, 2);
    }

    public function getFormattedValueAttribute()
    {
        return number_format($this->value, 2);
    }
}

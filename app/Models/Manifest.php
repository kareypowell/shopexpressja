<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manifest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'shipment_date',
        'reservation_number',
        'flight_number',
        'flight_destination',
        'exchange_rate',
        'type',
        'is_open',
    ];

    public function scopeSearch($query, $term)
    {
        return $query->where(
            fn($query) => $query->where('name', 'like', '%' . $term . '%')
                ->orWhere('reservation_number', 'like', '%' . $term . '%')
                ->orWhere('flight_number', 'like', '%' . $term . '%')
        );
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}

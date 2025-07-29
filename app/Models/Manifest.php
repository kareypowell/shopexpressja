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
        'vessel_name',
        'voyage_number',
        'departure_port',
        'arrival_port',
        'estimated_arrival_date',
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
                ->orWhere('vessel_name', 'like', '%' . $term . '%')
                ->orWhere('voyage_number', 'like', '%' . $term . '%')
        );
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Check if this is a sea manifest
     */
    public function isSeaManifest(): bool
    {
        return $this->type === 'sea';
    }

    /**
     * Get transport information based on manifest type
     */
    public function getTransportInfoAttribute(): array
    {
        if ($this->isSeaManifest()) {
            return [
                'vessel_name' => $this->vessel_name,
                'voyage_number' => $this->voyage_number,
                'departure_port' => $this->departure_port,
                'arrival_port' => $this->arrival_port,
                'estimated_arrival_date' => $this->estimated_arrival_date,
            ];
        }

        return [
            'flight_number' => $this->flight_number,
            'flight_destination' => $this->flight_destination,
        ];
    }
}

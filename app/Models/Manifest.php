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
        'is_open',
    ];
}

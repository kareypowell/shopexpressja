<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shs_number',
        'tax_number',
        'telephone_number',
        'street_address',
        'city_town',
        'parish',
        'country',
        'pickup_location',
        'profile_photo_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

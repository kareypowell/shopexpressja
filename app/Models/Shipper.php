<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipper extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function preAlerts()
    {
        return $this->hasMany(PreAlert::class);
    }
}

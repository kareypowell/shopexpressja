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

    public function manifests()
    {
        return $this->hasMany(Manifest::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagePreAlert extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function preAlert()
    {
        return $this->belongsTo(PreAlert::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function scopeSearch($query, $term)
    {
        return $query->where(
            fn($query) => $query->where('weight', 'like', '%' . $term . '%')
                ->orWhere('price', 'like', '%' . $term . '%')
                ->orWhere('type', 'like', '%' . $term . '%')
        );
    }
}

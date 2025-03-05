<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function scopeSearch($query, $term)
    {
        return $query->where(
            fn($query) => $query->where('item_name', 'like', '%' . $term . '%')
                ->orWhere('status', 'like', '%' . $term . '%')
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

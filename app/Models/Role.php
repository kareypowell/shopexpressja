<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // System roles that cannot be deleted
    const SYSTEM_ROLES = ['superadmin', 'admin', 'customer', 'purchaser'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'name' => 'string',
        'description' => 'string',
    ];

    /**
     * Boot the model and add model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure role names are always stored in lowercase for consistency
        static::creating(function ($role) {
            $role->name = strtolower(trim($role->name));
        });

        static::updating(function ($role) {
            $role->name = strtolower(trim($role->name));
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(
            fn($query) => $query->where('name', 'like', '%' . $term . '%')
                ->orWhere('description', 'like', '%' . $term . '%')
        );
    }

    /**
     * Get the count of users assigned to this role
     */
    public function getUserCount(): int
    {
        return $this->users()->count();
    }

    /**
     * Check if this is a system role that cannot be deleted
     */
    public function isSystemRole(): bool
    {
        return in_array(strtolower($this->name), self::SYSTEM_ROLES);
    }

    /**
     * Check if this role can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->isSystemRole() && $this->getUserCount() === 0;
    }

    /**
     * Scope for system roles
     */
    public function scopeSystemRoles($query)
    {
        return $query->whereIn('name', self::SYSTEM_ROLES);
    }

    /**
     * Scope for custom roles (non-system roles)
     */
    public function scopeCustomRoles($query)
    {
        return $query->whereNotIn('name', self::SYSTEM_ROLES);
    }
}

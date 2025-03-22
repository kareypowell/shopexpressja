<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MailerSend\LaravelDriver\MailerSendTrait;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, MailerSendTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function scopeSearch($query, $term)
    {
        return $query->where(
            fn($query) => $query->where('first_name', 'like', '%' . $term . '%')
                ->orWhere('last_name', 'like', '%' . $term . '%')
                ->orWhere('email', 'like', '%' . $term . '%')
                ->orWhereHas('profile', function($query) use ($term) {
                    $query->where('tax_number', 'like', '%' . $term . '%')
                          ->orWhere('account_number', 'like', '%' . $term . '%')
                          ->orWhere('telephone_number', 'like', '%' . $term . '%')
                          ->orWhere('parish', 'like', '%' . $term . '%');
                })
        );
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function preAlerts()
    {
        return $this->hasMany(PreAlert::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function packagePreAlerts()
    {
        return $this->hasMany(PackagePreAlert::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if the user has the given role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        // Convert the input role string to an array if it's a comma-separated string
        if (is_string($role) && strpos($role, ',') !== false) {
            $roles = array_map('trim', explode(',', $role));
            return in_array($this->role->name, $roles);
        }

        // If checking for a single role as a string
        if (is_string($role)) {
            return $this->role->name === $role;
        }

        // If checking for multiple roles passed as an array
        if (is_array($role)) {
            return in_array($this->role->name, $role);
        }

        return false;
    }

    /**
     * Check if the user has superadmin role.
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('superadmin');
    }

    /**
     * Check if the user has admin role.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user has customer role.
     *
     * @return bool
     */
    public function isCustomer()
    {
        return $this->hasRole('customer');
    }

    /**
     * Check if the user has purchaser role.
     *
     * @return bool
     */
    public function isPurchaser()
    {
        return $this->hasRole('purchaser');
    }
}

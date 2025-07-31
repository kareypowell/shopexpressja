<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MailerSend\LaravelDriver\MailerSendTrait;
use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, MailerSendTrait, SoftDeletes;

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
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

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

    /**
     * Scope to get only customers (role_id = 3).
     */
    public function scopeCustomers($query)
    {
        return $query->where('role_id', 3);
    }

    /**
     * Scope to get only active customers (not soft deleted).
     */
    public function scopeActiveCustomers($query)
    {
        return $query->customers()->whereNull('deleted_at');
    }

    /**
     * Scope to get only deleted customers.
     */
    public function scopeDeletedCustomers($query)
    {
        return $query->onlyTrashed()->where('role_id', 3);
    }

    /**
     * Scope to get all customers including soft deleted ones.
     */
    public function scopeAllCustomers($query)
    {
        return $query->withTrashed()->where('role_id', 3);
    }

    /**
     * Scope to get customers by status (active, deleted, all).
     */
    public function scopeByStatus($query, $status = 'active')
    {
        switch ($status) {
            case 'deleted':
                return $query->deletedCustomers();
            case 'all':
                return $query->allCustomers();
            case 'active':
            default:
                return $query->activeCustomers();
        }
    }

    /**
     * Scope to get customers with their profiles loaded.
     */
    public function scopeWithProfile($query)
    {
        return $query->with('profile');
    }

    /**
     * Scope to get customers with package statistics.
     */
    public function scopeWithPackageStats($query)
    {
        return $query->withCount('packages')
                    ->with(['packages' => function($query) {
                        $query->select('user_id', 'freight_price', 'customs_duty', 'storage_fee', 'delivery_fee', 'created_at');
                    }]);
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
     * Get the total amount spent by the customer across all packages.
     *
     * @return float
     */
    public function getTotalSpentAttribute(): float
    {
        return $this->packages()
            ->selectRaw('COALESCE(SUM(freight_price), 0) + COALESCE(SUM(customs_duty), 0) + COALESCE(SUM(storage_fee), 0) + COALESCE(SUM(delivery_fee), 0) as total')
            ->value('total') ?? 0.0;
    }

    /**
     * Get the total number of packages for the customer.
     *
     * @return int
     */
    public function getPackageCountAttribute(): int
    {
        return $this->packages()->count();
    }

    /**
     * Get the average package value for the customer.
     *
     * @return float
     */
    public function getAveragePackageValueAttribute(): float
    {
        $packageCount = $this->getPackageCountAttribute();
        if ($packageCount === 0) {
            return 0.0;
        }
        
        return $this->getTotalSpentAttribute() / $packageCount;
    }

    /**
     * Get the date of the customer's last shipment.
     *
     * @return Carbon|null
     */
    public function getLastShipmentDateAttribute(): ?Carbon
    {
        $lastPackage = $this->packages()->latest('created_at')->first();
        return $lastPackage ? $lastPackage->created_at : null;
    }

    /**
     * Get a comprehensive financial summary for the customer.
     *
     * @return array
     */
    public function getFinancialSummary(): array
    {
        $packages = $this->packages()
            ->selectRaw('
                COUNT(*) as total_packages,
                COALESCE(SUM(freight_price), 0) as total_freight,
                COALESCE(SUM(customs_duty), 0) as total_customs,
                COALESCE(SUM(storage_fee), 0) as total_storage,
                COALESCE(SUM(delivery_fee), 0) as total_delivery,
                COALESCE(AVG(freight_price), 0) as avg_freight,
                COALESCE(AVG(customs_duty), 0) as avg_customs,
                COALESCE(AVG(storage_fee), 0) as avg_storage,
                COALESCE(AVG(delivery_fee), 0) as avg_delivery
            ')
            ->first();

        $totalSpent = ($packages->total_freight ?? 0) + 
                     ($packages->total_customs ?? 0) + 
                     ($packages->total_storage ?? 0) + 
                     ($packages->total_delivery ?? 0);

        return [
            'total_packages' => $packages->total_packages ?? 0,
            'total_spent' => $totalSpent,
            'breakdown' => [
                'freight' => $packages->total_freight ?? 0,
                'customs' => $packages->total_customs ?? 0,
                'storage' => $packages->total_storage ?? 0,
                'delivery' => $packages->total_delivery ?? 0,
            ],
            'averages' => [
                'per_package' => $packages->total_packages > 0 ? $totalSpent / $packages->total_packages : 0,
                'freight' => $packages->avg_freight ?? 0,
                'customs' => $packages->avg_customs ?? 0,
                'storage' => $packages->avg_storage ?? 0,
                'delivery' => $packages->avg_delivery ?? 0,
            ]
        ];
    }

    /**
     * Get package statistics for the customer.
     *
     * @return array
     */
    public function getPackageStats(): array
    {
        $stats = $this->packages()
            ->selectRaw('
                COUNT(*) as total_packages,
                COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered_packages,
                COUNT(CASE WHEN status = "in_transit" THEN 1 END) as in_transit_packages,
                COUNT(CASE WHEN status = "ready_for_pickup" THEN 1 END) as ready_packages,
                COUNT(CASE WHEN status = "delayed" THEN 1 END) as delayed_packages,
                COALESCE(AVG(weight), 0) as avg_weight,
                COALESCE(SUM(weight), 0) as total_weight
            ')
            ->first();

        // Get shipping frequency (packages per month)
        $firstPackageDate = $this->packages()->oldest('created_at')->value('created_at');
        $monthsActive = $firstPackageDate ? 
            max(1, Carbon::parse($firstPackageDate)->diffInMonths(Carbon::now()) + 1) : 1;
        
        $shippingFrequency = ($stats->total_packages ?? 0) / $monthsActive;

        return [
            'total_packages' => $stats->total_packages ?? 0,
            'status_breakdown' => [
                'delivered' => $stats->delivered_packages ?? 0,
                'in_transit' => $stats->in_transit_packages ?? 0,
                'ready_for_pickup' => $stats->ready_packages ?? 0,
                'delayed' => $stats->delayed_packages ?? 0,
            ],
            'weight_stats' => [
                'total_weight' => $stats->total_weight ?? 0,
                'average_weight' => $stats->avg_weight ?? 0,
            ],
            'shipping_frequency' => round($shippingFrequency, 2),
            'months_active' => $monthsActive,
            'last_shipment' => $this->getLastShipmentDateAttribute(),
        ];
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
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
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

    /**
     * Soft delete a customer with validation.
     *
     * @return bool
     * @throws \Exception
     */
    public function softDeleteCustomer()
    {
        // Validate that this is a customer
        if (!$this->isCustomer()) {
            throw new \Exception('Only customers can be soft deleted through this method.');
        }

        // Validate that the customer is not already deleted
        if ($this->trashed()) {
            throw new \Exception('Customer is already deleted.');
        }

        // Validate that superadmins cannot be deleted
        if ($this->isSuperAdmin()) {
            throw new \Exception('Superadmin accounts cannot be deleted.');
        }

        return $this->delete();
    }

    /**
     * Restore a soft deleted customer with validation.
     *
     * @return bool
     * @throws \Exception
     */
    public function restoreCustomer()
    {
        // Validate that this is a customer
        if (!$this->isCustomer()) {
            throw new \Exception('Only customers can be restored through this method.');
        }

        // Validate that the customer is actually deleted
        if (!$this->trashed()) {
            throw new \Exception('Customer is not deleted and cannot be restored.');
        }

        // Check for email conflicts before restoring
        $existingUser = static::where('email', $this->email)
            ->whereNull('deleted_at')
            ->first();

        if ($existingUser) {
            throw new \Exception('Cannot restore customer: email address is already in use by another active user.');
        }

        return $this->restore();
    }

    /**
     * Check if the customer can be safely deleted.
     *
     * @return bool
     */
    public function canBeDeleted()
    {
        // Cannot delete if not a customer
        if (!$this->isCustomer()) {
            return false;
        }

        // Cannot delete if already deleted
        if ($this->trashed()) {
            return false;
        }

        // Cannot delete superadmins
        if ($this->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the customer can be restored.
     *
     * @return bool
     */
    public function canBeRestored()
    {
        // Can only restore customers
        if (!$this->isCustomer()) {
            return false;
        }

        // Can only restore if actually deleted
        if (!$this->trashed()) {
            return false;
        }

        // Check for email conflicts
        $existingUser = static::where('email', $this->email)
            ->whereNull('deleted_at')
            ->first();

        return !$existingUser;
    }

    /**
     * Get the deletion status information.
     *
     * @return array
     */
    public function getDeletionInfo()
    {
        return [
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at,
            'can_be_deleted' => $this->canBeDeleted(),
            'can_be_restored' => $this->canBeRestored(),
            'deletion_reason' => $this->getDeletionReason(),
        ];
    }

    /**
     * Get the reason why a customer cannot be deleted (if applicable).
     *
     * @return string|null
     */
    private function getDeletionReason()
    {
        if (!$this->isCustomer()) {
            return 'Only customers can be deleted';
        }

        if ($this->trashed()) {
            return 'Customer is already deleted';
        }

        if ($this->isSuperAdmin()) {
            return 'Superadmin accounts cannot be deleted';
        }

        return null;
    }
}

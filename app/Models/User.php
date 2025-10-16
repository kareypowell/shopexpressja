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
use App\Traits\Auditable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, MailerSendTrait, SoftDeletes, Auditable;

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
        'account_balance',
        'credit_balance',
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
        'account_balance' => 'decimal:2',
        'credit_balance' => 'decimal:2',
    ];

    /**
     * Fields to exclude from audit logging
     *
     * @var array<string>
     */
    protected $auditExcluded = [
        'password',
        'remember_token',
        'api_token',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * Cache for the user's role to avoid repeated database queries.
     *
     * @var Role|null
     */
    protected $roleCache = null;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        // Split search term into individual words for better matching
        $searchTerms = explode(' ', trim($term));
        
        return $query->where(function($query) use ($searchTerms, $term) {
            // Search in user fields
            $query->where(function($q) use ($searchTerms, $term) {
                foreach ($searchTerms as $searchTerm) {
                    $q->where('first_name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('email', 'like', '%' . $searchTerm . '%');
                }
                // Also search for full term
                $q->orWhere('first_name', 'like', '%' . $term . '%')
                  ->orWhere('last_name', 'like', '%' . $term . '%')
                  ->orWhere('email', 'like', '%' . $term . '%');
            })
            // Search in profile fields
            ->orWhereHas('profile', function($profileQuery) use ($searchTerms, $term) {
                $profileQuery->where(function($q) use ($searchTerms, $term) {
                    foreach ($searchTerms as $searchTerm) {
                        $q->where('tax_number', 'like', '%' . $searchTerm . '%')
                          ->orWhere('account_number', 'like', '%' . $searchTerm . '%')
                          ->orWhere('telephone_number', 'like', '%' . $searchTerm . '%')
                          ->orWhere('parish', 'like', '%' . $searchTerm . '%')
                          ->orWhere('street_address', 'like', '%' . $searchTerm . '%')
                          ->orWhere('city_town', 'like', '%' . $searchTerm . '%')
                          ->orWhere('country', 'like', '%' . $searchTerm . '%')
                          ->orWhere('pickup_location', 'like', '%' . $searchTerm . '%');
                    }
                    // Also search for full term in profile fields
                    $q->orWhere('tax_number', 'like', '%' . $term . '%')
                      ->orWhere('account_number', 'like', '%' . $term . '%')
                      ->orWhere('telephone_number', 'like', '%' . $term . '%')
                      ->orWhere('parish', 'like', '%' . $term . '%')
                      ->orWhere('street_address', 'like', '%' . $term . '%')
                      ->orWhere('city_town', 'like', '%' . $term . '%')
                      ->orWhere('country', 'like', '%' . $term . '%')
                      ->orWhere('pickup_location', 'like', '%' . $term . '%');
                });
            })
            // Search for full name combinations
            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $term . '%'])
            ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ['%' . $term . '%']);
        });
    }

    /**
     * Advanced search scope with field-specific search capabilities
     */
    public function scopeAdvancedSearch($query, array $criteria)
    {
        foreach ($criteria as $field => $value) {
            if (empty($value)) {
                continue;
            }

            switch ($field) {
                case 'name':
                    $query->where(function($q) use ($value) {
                        $q->where('first_name', 'like', '%' . $value . '%')
                          ->orWhere('last_name', 'like', '%' . $value . '%')
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $value . '%']);
                    });
                    break;
                
                case 'email':
                    $query->where('email', 'like', '%' . $value . '%');
                    break;
                
                case 'account_number':
                    $query->whereHas('profile', fn($q) => $q->where('account_number', 'like', '%' . $value . '%'));
                    break;
                
                case 'tax_number':
                    $query->whereHas('profile', fn($q) => $q->where('tax_number', 'like', '%' . $value . '%'));
                    break;
                
                case 'telephone_number':
                    $query->whereHas('profile', fn($q) => $q->where('telephone_number', 'like', '%' . $value . '%'));
                    break;
                
                case 'parish':
                    $query->whereHas('profile', fn($q) => $q->where('parish', $value));
                    break;
                
                case 'address':
                    $query->whereHas('profile', function($q) use ($value) {
                        $q->where('street_address', 'like', '%' . $value . '%')
                          ->orWhere('city_town', 'like', '%' . $value . '%');
                    });
                    break;
                
                case 'registration_date_from':
                    $query->whereDate('created_at', '>=', $value);
                    break;
                
                case 'registration_date_to':
                    $query->whereDate('created_at', '<=', $value);
                    break;
                
                case 'status':
                    if ($value === 'active') {
                        $query->whereNull('deleted_at');
                    } elseif ($value === 'deleted') {
                        $query->whereNotNull('deleted_at');
                    }
                    // 'all' doesn't add any condition
                    break;
            }
        }

        return $query;
    }

    /**
     * Scope to get only customers.
     */
    public function scopeCustomers($query)
    {
        $customerRole = Role::where('name', 'customer')->first();
        return $query->where('role_id', $customerRole ? $customerRole->id : 3);
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
        return $query->onlyTrashed()->customerUsers();
    }

    /**
     * Scope to get all customers including soft deleted ones.
     */
    public function scopeAllCustomers($query)
    {
        return $query->withTrashed()->customerUsers();
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
     * Scope to get users with a specific role.
     */
    public function scopeWithRole($query, $roleName)
    {
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('name', strtolower($roleName));
        });
    }

    /**
     * Scope to get only admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->withRole('admin');
    }

    /**
     * Scope to get only superadmin users.
     */
    public function scopeSuperAdmins($query)
    {
        return $query->withRole('superadmin');
    }

    /**
     * Scope to get only purchaser users.
     */
    public function scopePurchasers($query)
    {
        return $query->withRole('purchaser');
    }

    /**
     * Scope to get only customer users (using new role-based approach).
     */
    public function scopeCustomerUsers($query)
    {
        return $query->withRole('customer');
    }

    /**
     * Scope to get users with any of the given roles.
     */
    public function scopeWithAnyRole($query, array $roles)
    {
        return $query->whereHas('role', function($q) use ($roles) {
            $q->whereIn('name', array_map('strtolower', $roles));
        });
    }

    /**
     * Scope to get users excluding specific roles.
     */
    public function scopeWithoutRole($query, $roleName)
    {
        return $query->whereDoesntHave('role', function($q) use ($roleName) {
            $q->where('name', strtolower($roleName));
        });
    }

    /**
     * Scope to get users excluding any of the given roles.
     */
    public function scopeWithoutAnyRole($query, array $roles)
    {
        return $query->whereDoesntHave('role', function($q) use ($roles) {
            $q->whereIn('name', array_map('strtolower', $roles));
        });
    }

    /**
     * Scope to get customers with their profiles loaded.
     */
    public function scopeWithProfile($query)
    {
        return $query->with('profile');
    }

    /**
     * Scope to get customers with optimized profile loading.
     */
    public function scopeWithOptimizedProfile($query)
    {
        return $query->with(['profile' => function($query) {
            $query->select('user_id', 'account_number', 'tax_number', 'telephone_number', 
                          'parish', 'city_town', 'street_address', 'country', 'pickup_location');
        }]);
    }

    /**
     * Scope to get customers with package statistics.
     */
    public function scopeWithPackageStats($query)
    {
        return $query->withCount('packages')
                    ->with(['packages' => function($query) {
                        $query->select('user_id', 'freight_price', 'clearance_fee', 'storage_fee', 'delivery_fee', 'created_at');
                    }]);
    }

    /**
     * Scope to get customers with optimized package loading for statistics.
     */
    public function scopeWithOptimizedPackages($query)
    {
        return $query->withCount([
                'packages',
                'packages as delivered_packages_count' => function($query) {
                    $query->where('status', 'delivered');
                },
                'packages as in_transit_packages_count' => function($query) {
                    $query->where('status', 'in_transit');
                },
                'packages as ready_packages_count' => function($query) {
                    $query->where('status', 'ready_for_pickup');
                }
            ])
            ->withSum('packages', 'freight_price')
            ->withSum('packages', 'clearance_fee')
            ->withSum('packages', 'storage_fee')
            ->withSum('packages', 'delivery_fee')
            ->withAvg('packages', 'weight');
    }

    /**
     * Scope to get customers with recent activity.
     */
    public function scopeWithRecentActivity($query, int $days = 30)
    {
        return $query->withCount(['packages as recent_packages_count' => function($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }]);
    }

    /**
     * Scope for efficient customer table queries.
     */
    public function scopeForCustomerTable($query)
    {
        return $query->customers()
            ->withOptimizedProfile()
            ->withCount('packages')
            ->select('id', 'first_name', 'last_name', 'email', 'created_at', 'deleted_at', 'role_id');
    }

    /**
     * Scope for efficient customer search queries.
     */
    public function scopeForCustomerSearch($query, $searchTerm = null)
    {
        $query = $query->customers()
            ->withOptimizedProfile()
            ->select('id', 'first_name', 'last_name', 'email', 'created_at', 'deleted_at', 'role_id');
            
        if ($searchTerm) {
            $query->search($searchTerm);
        }
        
        return $query;
    }

    /**
     * Scope for customer dashboard queries with minimal data.
     */
    public function scopeForDashboard($query)
    {
        return $query->customers()
            ->select('id', 'first_name', 'last_name', 'email', 'created_at')
            ->withCount([
                'packages',
                'packages as recent_packages_count' => function($query) {
                    $query->where('created_at', '>=', now()->subMonth());
                }
            ]);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function consolidatedPackages()
    {
        return $this->hasMany(ConsolidatedPackage::class, 'customer_id');
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

    public function roleChangeAudits()
    {
        return $this->hasMany(RoleChangeAudit::class, 'user_id');
    }

    public function changedRoleAudits()
    {
        return $this->hasMany(RoleChangeAudit::class, 'changed_by_user_id');
    }

    /**
     * Get the total amount spent by the customer across all packages.
     *
     * @return float
     */
    public function getTotalSpentAttribute(): float
    {
        return $this->packages()
            ->selectRaw('COALESCE(SUM(freight_price), 0) + COALESCE(SUM(clearance_fee), 0) + COALESCE(SUM(storage_fee), 0) + COALESCE(SUM(delivery_fee), 0) as total')
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
                COALESCE(SUM(clearance_fee), 0) as total_clearance,
                COALESCE(SUM(storage_fee), 0) as total_storage,
                COALESCE(SUM(delivery_fee), 0) as total_delivery,
                COALESCE(AVG(freight_price), 0) as avg_freight,
                COALESCE(AVG(clearance_fee), 0) as avg_clearance,
                COALESCE(AVG(storage_fee), 0) as avg_storage,
                COALESCE(AVG(delivery_fee), 0) as avg_delivery,
                COALESCE(MAX(freight_price + clearance_fee + storage_fee + delivery_fee), 0) as highest_package_cost,
                COALESCE(MIN(freight_price + clearance_fee + storage_fee + delivery_fee), 0) as lowest_package_cost
            ')
            ->first();

        $totalSpent = ($packages->total_freight ?? 0) + 
                     ($packages->total_clearance ?? 0) + 
                     ($packages->total_storage ?? 0) + 
                     ($packages->total_delivery ?? 0);

        // Calculate cost distribution percentages
        $freightPercentage = $totalSpent > 0 ? (($packages->total_freight ?? 0) / $totalSpent) * 100 : 0;
        $clearancePercentage = $totalSpent > 0 ? (($packages->total_clearance ?? 0) / $totalSpent) * 100 : 0;
        $storagePercentage = $totalSpent > 0 ? (($packages->total_storage ?? 0) / $totalSpent) * 100 : 0;
        $deliveryPercentage = $totalSpent > 0 ? (($packages->total_delivery ?? 0) / $totalSpent) * 100 : 0;

        return [
            'total_packages' => $packages->total_packages ?? 0,
            'total_spent' => round($totalSpent, 2),
            'breakdown' => [
                'freight' => round($packages->total_freight ?? 0, 2),
                'clearance' => round($packages->total_clearance ?? 0, 2),
                'storage' => round($packages->total_storage ?? 0, 2),
                'delivery' => round($packages->total_delivery ?? 0, 2),
            ],
            'cost_percentages' => [
                'freight' => round($freightPercentage, 1),
                'clearance' => round($clearancePercentage, 1),
                'storage' => round($storagePercentage, 1),
                'delivery' => round($deliveryPercentage, 1),
            ],
            'averages' => [
                'per_package' => $packages->total_packages > 0 ? round($totalSpent / $packages->total_packages, 2) : 0,
                'freight' => round($packages->avg_freight ?? 0, 2),
                'clearance' => round($packages->avg_clearance ?? 0, 2),
                'storage' => round($packages->avg_storage ?? 0, 2),
                'delivery' => round($packages->avg_delivery ?? 0, 2),
            ],
            'cost_range' => [
                'highest_package' => round($packages->highest_package_cost ?? 0, 2),
                'lowest_package' => round($packages->lowest_package_cost ?? 0, 2),
            ]
        ];
    }

    /**
     * Get total spending calculation methods by category
     *
     * @return array
     */
    public function getTotalSpendingByCategory(): array
    {
        $categoryTotals = $this->packages()
            ->selectRaw('
                COALESCE(SUM(freight_price), 0) as freight_total,
                COALESCE(SUM(clearance_fee), 0) as clearance_total,
                COALESCE(SUM(storage_fee), 0) as storage_total,
                COALESCE(SUM(delivery_fee), 0) as delivery_total,
                COUNT(*) as package_count
            ')
            ->first();

        $grandTotal = ($categoryTotals->freight_total ?? 0) + 
                     ($categoryTotals->clearance_total ?? 0) + 
                     ($categoryTotals->storage_total ?? 0) + 
                     ($categoryTotals->delivery_total ?? 0);

        return [
            'freight' => [
                'total' => round($categoryTotals->freight_total ?? 0, 2),
                'percentage' => $grandTotal > 0 ? round((($categoryTotals->freight_total ?? 0) / $grandTotal) * 100, 1) : 0,
                'average_per_package' => $categoryTotals->package_count > 0 ? round(($categoryTotals->freight_total ?? 0) / $categoryTotals->package_count, 2) : 0,
            ],
            'clearance' => [
                'total' => round($categoryTotals->clearance_total ?? 0, 2),
                'percentage' => $grandTotal > 0 ? round((($categoryTotals->clearance_total ?? 0) / $grandTotal) * 100, 1) : 0,
                'average_per_package' => $categoryTotals->package_count > 0 ? round(($categoryTotals->clearance_total ?? 0) / $categoryTotals->package_count, 2) : 0,
            ],
            'storage' => [
                'total' => round($categoryTotals->storage_total ?? 0, 2),
                'percentage' => $grandTotal > 0 ? round((($categoryTotals->storage_total ?? 0) / $grandTotal) * 100, 1) : 0,
                'average_per_package' => $categoryTotals->package_count > 0 ? round(($categoryTotals->storage_total ?? 0) / $categoryTotals->package_count, 2) : 0,
            ],
            'delivery' => [
                'total' => round($categoryTotals->delivery_total ?? 0, 2),
                'percentage' => $grandTotal > 0 ? round((($categoryTotals->delivery_total ?? 0) / $grandTotal) * 100, 1) : 0,
                'average_per_package' => $categoryTotals->package_count > 0 ? round(($categoryTotals->delivery_total ?? 0) / $categoryTotals->package_count, 2) : 0,
            ],
            'grand_total' => round($grandTotal, 2),
            'package_count' => $categoryTotals->package_count ?? 0,
        ];
    }

    /**
     * Get average package value calculations
     *
     * @return array
     */
    public function getAveragePackageValueCalculations(): array
    {
        $stats = $this->packages()
            ->selectRaw('
                COUNT(*) as total_packages,
                COALESCE(AVG(freight_price + clearance_fee + storage_fee + delivery_fee), 0) as avg_total_cost,
                COALESCE(AVG(freight_price), 0) as avg_freight,
                COALESCE(AVG(clearance_fee), 0) as avg_clearance,
                COALESCE(AVG(storage_fee), 0) as avg_storage,
                COALESCE(AVG(delivery_fee), 0) as avg_delivery,
                COALESCE(AVG(weight), 0) as avg_weight,
                COALESCE(AVG(estimated_value), 0) as avg_estimated_value
            ')
            ->first();

        return [
            'total_cost' => round($stats->avg_total_cost ?? 0, 2),
            'by_category' => [
                'freight' => round($stats->avg_freight ?? 0, 2),
                'clearance' => round($stats->avg_clearance ?? 0, 2),
                'storage' => round($stats->avg_storage ?? 0, 2),
                'delivery' => round($stats->avg_delivery ?? 0, 2),
            ],
            'cost_per_weight' => $stats->avg_weight > 0 ? round(($stats->avg_total_cost ?? 0) / $stats->avg_weight, 2) : 0,
            'average_weight' => round($stats->avg_weight ?? 0, 2),
            'average_estimated_value' => round($stats->avg_estimated_value ?? 0, 2),
            'package_count' => $stats->total_packages ?? 0,
        ];
    }

    /**
     * Get financial trend analysis over time
     *
     * @param int $months Number of months to analyze (default: 12)
     * @return array
     */
    public function getFinancialTrendAnalysis(int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months);
        
        // Get packages and group them manually to avoid database-specific functions
        $packages = $this->packages()
            ->where('created_at', '>=', $startDate)
            ->get();

        // Group packages by month manually
        $monthlyDataRaw = [];
        foreach ($packages as $package) {
            $year = $package->created_at->year;
            $month = $package->created_at->month;
            $key = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
            
            if (!isset($monthlyDataRaw[$key])) {
                $monthlyDataRaw[$key] = (object) [
                    'year' => $year,
                    'month' => $month,
                    'package_count' => 0,
                    'total_spent' => 0,
                ];
            }
            
            $monthlyDataRaw[$key]->package_count++;
            $monthlyDataRaw[$key]->total_spent += ($package->freight_price + $package->clearance_fee + $package->storage_fee + $package->delivery_fee);
        }

        // Sort by key and calculate averages
        ksort($monthlyDataRaw);
        $monthlyData = [];
        foreach ($monthlyDataRaw as $data) {
            $data->avg_cost_per_package = $data->package_count > 0 ? $data->total_spent / $data->package_count : 0;
            $monthlyData[] = $data;
        }

        $trends = [];
        $totalSpending = 0;
        $totalPackages = 0;
        $monthlySpending = [];

        foreach ($monthlyData as $data) {
            $monthKey = $data->year . '-' . str_pad($data->month, 2, '0', STR_PAD_LEFT);
            $monthName = Carbon::createFromDate($data->year, $data->month, 1)->format('M Y');
            
            $monthlySpending[] = $data->total_spent;
            $totalSpending += $data->total_spent;
            $totalPackages += $data->package_count;
            
            $trends[] = [
                'month' => $monthName,
                'year' => (int) $data->year,
                'month_number' => (int) $data->month,
                'package_count' => $data->package_count,
                'total_spent' => round($data->total_spent, 2),
                'average_per_package' => round($data->avg_cost_per_package, 2),
            ];
        }

        // Calculate trend indicators
        $trendDirection = 'stable';
        $trendPercentage = 0;
        
        if (count($monthlySpending) >= 2) {
            $firstHalf = array_slice($monthlySpending, 0, ceil(count($monthlySpending) / 2));
            $secondHalf = array_slice($monthlySpending, floor(count($monthlySpending) / 2));
            
            $firstHalfAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
            $secondHalfAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;
            
            if ($firstHalfAvg > 0) {
                $trendPercentage = (($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg) * 100;
                
                if ($trendPercentage > 10) {
                    $trendDirection = 'increasing';
                } elseif ($trendPercentage < -10) {
                    $trendDirection = 'decreasing';
                }
            }
        }

        return [
            'period_months' => $months,
            'monthly_trends' => $trends,
            'summary' => [
                'total_spent' => round($totalSpending, 2),
                'total_packages' => $totalPackages,
                'average_monthly_spending' => count($monthlyData) > 0 ? round($totalSpending / count($monthlyData), 2) : 0,
                'average_monthly_packages' => count($monthlyData) > 0 ? round($totalPackages / count($monthlyData), 1) : 0,
            ],
            'trend_analysis' => [
                'direction' => $trendDirection,
                'percentage_change' => round($trendPercentage, 1),
                'description' => $this->getTrendDescription($trendDirection, $trendPercentage),
            ]
        ];
    }

    /**
     * Get a description of the spending trend
     *
     * @param string $direction
     * @param float $percentage
     * @return string
     */
    private function getTrendDescription(string $direction, float $percentage): string
    {
        switch ($direction) {
            case 'increasing':
                return "Spending has increased by " . abs(round($percentage, 1)) . "% over the analysis period";
            case 'decreasing':
                return "Spending has decreased by " . abs(round($percentage, 1)) . "% over the analysis period";
            default:
                return "Spending has remained relatively stable over the analysis period";
        }
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
                COUNT(CASE WHEN status = "shipped" THEN 1 END) as in_transit_packages,
                COUNT(CASE WHEN status = "ready" THEN 1 END) as ready_packages,
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
            'total_count' => $stats->total_packages ?? 0, // Alias for backward compatibility
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
     * @param string|array $role
     * @return bool
     */
    public function hasRole($role)
    {
        $userRole = $this->getCachedRole();

        // Return false if user has no role assigned
        if (!$userRole) {
            return false;
        }

        // Convert the input role string to an array if it's a comma-separated string
        if (is_string($role) && strpos($role, ',') !== false) {
            $roles = array_map('trim', explode(',', $role));
            return in_array(strtolower($userRole->name), array_map('strtolower', $roles));
        }

        // If checking for a single role as a string
        if (is_string($role)) {
            return strtolower($userRole->name) === strtolower($role);
        }

        // If checking for multiple roles passed as an array
        if (is_array($role)) {
            return in_array(strtolower($userRole->name), array_map('strtolower', $role));
        }

        return false;
    }

    /**
     * Get the cached role for the user to avoid repeated database queries.
     *
     * @return Role|null
     */
    public function getCachedRole()
    {
        if ($this->roleCache === null) {
            // Load role relationship if not already loaded
            if (!$this->relationLoaded('role')) {
                $this->load('role');
            }
            $this->roleCache = $this->role;
        }

        return $this->roleCache;
    }

    /**
     * Clear the role cache. Useful when role is changed.
     *
     * @return void
     */
    public function clearRoleCache()
    {
        $this->roleCache = null;
        $this->unsetRelation('role');
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles)
    {
        $userRole = $this->getCachedRole();

        if (!$userRole) {
            return false;
        }

        return in_array(strtolower($userRole->name), array_map('strtolower', $roles));
    }

    /**
     * Check if the user has all of the given roles.
     * Note: Since a user can only have one role in this system, this will only return true
     * if the array contains exactly one role that matches the user's role.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAllRoles(array $roles)
    {
        $userRole = $this->getCachedRole();

        if (!$userRole || empty($roles)) {
            return false;
        }

        // Since users can only have one role, they can only have "all roles" 
        // if the array contains exactly one role that matches theirs
        if (count($roles) === 1) {
            return in_array(strtolower($userRole->name), array_map('strtolower', $roles));
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
     * Get the user's name (alias for full_name for compatibility).
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->getFullNameAttribute();
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
     * Check if the user can manage other users.
     *
     * @return bool
     */
    public function canManageUsers()
    {
        return $this->hasAnyRole(['admin', 'superadmin']);
    }

    /**
     * Check if the user can manage roles.
     *
     * @return bool
     */
    public function canManageRoles()
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if the user can access the admin panel.
     *
     * @return bool
     */
    public function canAccessAdminPanel()
    {
        return $this->hasAnyRole(['admin', 'superadmin']);
    }

    /**
     * Get the user's role name.
     *
     * @return string|null
     */
    public function getRoleName()
    {
        $role = $this->getCachedRole();
        return $role ? $role->name : null;
    }

    /**
     * Get the user's role description.
     *
     * @return string|null
     */
    public function getRoleDescription()
    {
        $role = $this->getCachedRole();
        return $role ? $role->description : null;
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

    /**
     * Customer account balance and transaction methods
     */
    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    /**
     * Get formatted account balance
     */
    public function getFormattedAccountBalanceAttribute()
    {
        return number_format($this->account_balance, 2);
    }

    /**
     * Get formatted credit balance
     */
    public function getFormattedCreditBalanceAttribute()
    {
        return number_format($this->credit_balance, 2);
    }

    /**
     * Get total available balance (account + credit)
     */
    public function getTotalAvailableBalanceAttribute()
    {
        return $this->account_balance + $this->credit_balance;
    }

    /**
     * Get formatted total available balance
     */
    public function getFormattedTotalAvailableBalanceAttribute()
    {
        return number_format($this->total_available_balance, 2);
    }

    /**
     * Get total cost of ready packages (pending charges)
     */
    public function getPendingPackageChargesAttribute()
    {
        return $this->packages()
            ->where('status', 'ready')
            ->sum(\DB::raw('freight_price + clearance_fee + storage_fee + delivery_fee'));
    }

    /**
     * Get actual outstanding amount based on non-delivered package statuses
     * This is the correct way to calculate outstanding amounts - only count packages
     * that are actually still outstanding based on their status
     * 
     * Outstanding = Sum of packages with non-delivered status (ready, customs, pending, processing, shipped, delayed)
     */
    public function getActualOutstandingAmount()
    {
        return $this->packages()
            ->whereIn('status', ['ready', 'customs', 'pending', 'processing', 'shipped', 'delayed'])
            ->sum(\DB::raw('COALESCE(freight_price, 0) + COALESCE(clearance_fee, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)'));
    }

    /**
     * Get collection metrics (DEPRECATED - INCORRECT CALCULATION)
     * 
     * @deprecated This method uses incorrect calculation logic. Use getActualOutstandingAmount() instead.
     * 
     * The problem with this method:
     * Outstanding = Total Owed - All Collections - All Write-offs
     * 
     * This is incorrect because it subtracts collections and write-offs for delivered packages
     * from the total, even though those packages shouldn't be considered "outstanding" anymore.
     */
    public function getCollectionMetrics()
    {
        // Get total amount owed across all packages
        $totalOwed = $this->packages()
            ->sum(\DB::raw('COALESCE(freight_price, 0) + COALESCE(clearance_fee, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)'));

        // Get total collections (payments received)
        $totalCollections = $this->transactions()
            ->whereIn('type', ['payment', 'credit'])
            ->sum('amount');

        // Get total write-offs
        $totalWriteOffs = $this->transactions()
            ->where('type', 'write_off')
            ->sum('amount');

        // INCORRECT CALCULATION - includes delivered packages
        $incorrectOutstanding = $totalOwed - $totalCollections - $totalWriteOffs;

        return [
            'total_owed' => $totalOwed,
            'total_collections' => $totalCollections,
            'total_write_offs' => $totalWriteOffs,
            'outstanding_balance' => max(0, $incorrectOutstanding), // This is wrong!
        ];
    }

    /**
     * Compare old vs new outstanding calculation methods
     * This method demonstrates the difference between the incorrect and correct calculations
     */
    public function compareOutstandingCalculations()
    {
        // Old incorrect method
        $oldMetrics = $this->getCollectionMetrics();
        
        // New correct method
        $correctOutstanding = $this->getActualOutstandingAmount();
        
        // Additional breakdown for clarity
        $deliveredPackagesTotal = $this->packages()
            ->where('status', 'delivered')
            ->sum(\DB::raw('COALESCE(freight_price, 0) + COALESCE(clearance_fee, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)'));
            
        $nonDeliveredPackagesTotal = $this->packages()
            ->whereIn('status', ['ready', 'customs', 'pending', 'processing', 'shipped', 'delayed'])
            ->sum(\DB::raw('COALESCE(freight_price, 0) + COALESCE(clearance_fee, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)'));

        return [
            'old_calculation' => [
                'method' => 'Total Owed - All Collections - All Write-offs',
                'total_owed' => $oldMetrics['total_owed'],
                'total_collections' => $oldMetrics['total_collections'],
                'total_write_offs' => $oldMetrics['total_write_offs'],
                'outstanding_balance' => $oldMetrics['outstanding_balance'],
                'problem' => 'Includes collections/write-offs for delivered packages'
            ],
            'new_calculation' => [
                'method' => 'Sum of packages with non-delivered status',
                'outstanding_balance' => $correctOutstanding,
                'explanation' => 'Only counts packages that are actually still outstanding'
            ],
            'breakdown' => [
                'delivered_packages_total' => $deliveredPackagesTotal,
                'non_delivered_packages_total' => $nonDeliveredPackagesTotal,
                'total_all_packages' => $deliveredPackagesTotal + $nonDeliveredPackagesTotal
            ],
            'difference' => [
                'amount' => $oldMetrics['outstanding_balance'] - $correctOutstanding,
                'explanation' => 'The difference represents the incorrect inclusion of delivered package amounts in the old calculation'
            ]
        ];
    }

    /**
     * Get formatted pending package charges
     */
    public function getFormattedPendingPackageChargesAttribute()
    {
        return number_format($this->pending_package_charges, 2);
    }

    /**
     * Get total amount customer needs to pay to collect all ready packages
     * This includes current debt minus available balance plus pending charges
     */
    public function getTotalAmountNeededAttribute()
    {
        $currentDebt = $this->account_balance < 0 ? abs($this->account_balance) : 0;
        $availableCredit = $this->credit_balance;
        $pendingCharges = $this->pending_package_charges;
        
        // Total needed = current debt + pending charges - available credit
        $totalNeeded = $currentDebt + $pendingCharges - $availableCredit;
        
        return max(0, $totalNeeded); // Never show negative amount needed
    }

    /**
     * Get formatted total amount needed
     */
    public function getFormattedTotalAmountNeededAttribute()
    {
        return number_format($this->total_amount_needed, 2);
    }

    /**
     * Check if customer has sufficient balance for a given amount
     */
    public function hasSufficientBalance($amount)
    {
        return $this->total_available_balance >= $amount;
    }

    /**
     * Add credit to customer account
     */
    public function addCredit($amount, $description, $createdBy = null, $referenceType = null, $referenceId = null, $metadata = null)
    {
        $balanceBefore = $this->account_balance;
        $this->account_balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => CustomerTransaction::TYPE_CREDIT,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->account_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Add overpayment credit to customer credit balance
     */
    public function addOverpaymentCredit($amount, $description, $createdBy = null, $referenceType = null, $referenceId = null, $metadata = null)
    {
        $creditBalanceBefore = $this->credit_balance;
        $this->credit_balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => CustomerTransaction::TYPE_CREDIT,
            'amount' => $amount,
            'balance_before' => $creditBalanceBefore,
            'balance_after' => $this->credit_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Record a write-off/discount transaction
     */
    public function recordWriteOff($amount, $description, $createdBy = null, $referenceType = null, $referenceId = null, $metadata = null)
    {
        // Write-offs are recorded as credits but don't affect account balance
        // They represent forgiven debt or discounts given
        return $this->transactions()->create([
            'type' => 'write_off',
            'amount' => $amount,
            'balance_before' => $this->account_balance,
            'balance_after' => $this->account_balance, // Balance doesn't change for write-offs
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Deduct amount from customer account
     */
    public function deductBalance($amount, $description, $createdBy = null, $referenceType = null, $referenceId = null, $metadata = null)
    {
        $balanceBefore = $this->account_balance;
        $this->account_balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => CustomerTransaction::TYPE_DEBIT,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->account_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Apply credit balance to a charge
     */
    public function applyCreditBalance($amount, $description, $createdBy = null, $referenceType = null, $referenceId = null, $metadata = null)
    {
        $creditToApply = min($amount, $this->credit_balance);
        
        if ($creditToApply > 0) {
            $balanceBefore = $this->credit_balance;
            $this->credit_balance -= $creditToApply;
            $this->save();

            $this->transactions()->create([
                'type' => CustomerTransaction::TYPE_DEBIT,
                'amount' => $creditToApply,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->credit_balance,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $createdBy,
                'metadata' => $metadata,
            ]);
        }

        return $creditToApply;
    }

    /**
     * Record a payment transaction
     */
    public function recordPayment($amount, $description, $createdBy = null, $referenceType = null, $referenceId = null, $metadata = null)
    {
        $balanceBefore = $this->account_balance;
        $this->account_balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => CustomerTransaction::TYPE_PAYMENT,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->account_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Record a payment transaction linked to a manifest
     */
    public function recordPaymentForManifest($amount, $description, Manifest $manifest, $createdBy = null, $metadata = null)
    {
        return $this->recordPayment(
            $amount,
            $description,
            $createdBy,
            'App\\Models\\Manifest',
            $manifest->id,
            $metadata
        );
    }

    /**
     * Record a charge transaction
     */
    public function recordCharge($amount, $description, $createdBy = null, $referenceType = null, $referenceId = null, $metadata = null)
    {
        $balanceBefore = $this->account_balance;
        $this->account_balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => CustomerTransaction::TYPE_CHARGE,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->account_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Record a charge transaction linked to a manifest
     */
    public function recordChargeForManifest($amount, $description, Manifest $manifest, $createdBy = null, $metadata = null)
    {
        return $this->recordCharge(
            $amount,
            $description,
            $createdBy,
            'App\\Models\\Manifest',
            $manifest->id,
            $metadata
        );
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions($limit = 10)
    {
        return $this->transactions()
            ->with('createdBy')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get account balance summary
     */
    public function getAccountBalanceSummary()
    {
        $recentTransactions = $this->getRecentTransactions(5);
        
        return [
            'account_balance' => $this->account_balance,
            'credit_balance' => $this->credit_balance,
            'total_available' => $this->total_available_balance,
            'formatted' => [
                'account_balance' => $this->formatted_account_balance,
                'credit_balance' => $this->formatted_credit_balance,
                'total_available' => $this->formatted_total_available_balance,
            ],
            'recent_transactions' => $recentTransactions,
        ];
    }

    /**
     * Check if user can access role management
     *
     * @return bool
     */
    public function canAccessRoleManagement(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user can access backup management
     *
     * @return bool
     */
    public function canAccessBackupManagement(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user can access audit logs
     *
     * @return bool
     */
    public function canAccessAuditLogs(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user can access administration section
     *
     * @return bool
     */
    public function canAccessAdministration(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    /**
     * Get allowed administration sections for the user
     *
     * @return array
     */
    public function getAllowedAdministrationSections(): array
    {
        $sections = [];
        
        if ($this->canAccessAdministration()) {
            $sections[] = 'user_management';
            $sections[] = 'offices';
            $sections[] = 'shipping_addresses';
            
            if ($this->isSuperAdmin()) {
                $sections[] = 'role_management';
                $sections[] = 'backup_management';
            }
        }
        
        return $sections;
    }

    /**
     * Get audit context for this user
     */
    public function getAuditContext(): array
    {
        return [
            'user_role' => $this->role->name ?? 'unknown',
            'user_type' => $this->isCustomer() ? 'customer' : ($this->isAdmin() ? 'admin' : 'superadmin'),
            'account_balance' => $this->account_balance,
            'is_active' => !$this->trashed(),
        ];
    }

    /**
     * Get audit relationship context
     */
    public function getAuditRelationshipContext(): array
    {
        return [
            'profile_id' => $this->profile->id,
            'role_id' => $this->role_id,
            'packages_count' => $this->packages()->count(),
        ];
    }

    /**
     * Custom audit condition for users
     */
    public function auditCondition(): bool
    {
        // Always audit all user actions for comprehensive tracking
        return true;
    }
}

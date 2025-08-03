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
                        $query->select('user_id', 'freight_price', 'customs_duty', 'storage_fee', 'delivery_fee', 'created_at');
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
            ->withSum('packages', 'customs_duty')
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
                COALESCE(AVG(delivery_fee), 0) as avg_delivery,
                COALESCE(MAX(freight_price + customs_duty + storage_fee + delivery_fee), 0) as highest_package_cost,
                COALESCE(MIN(freight_price + customs_duty + storage_fee + delivery_fee), 0) as lowest_package_cost
            ')
            ->first();

        $totalSpent = ($packages->total_freight ?? 0) + 
                     ($packages->total_customs ?? 0) + 
                     ($packages->total_storage ?? 0) + 
                     ($packages->total_delivery ?? 0);

        // Calculate cost distribution percentages
        $freightPercentage = $totalSpent > 0 ? (($packages->total_freight ?? 0) / $totalSpent) * 100 : 0;
        $customsPercentage = $totalSpent > 0 ? (($packages->total_customs ?? 0) / $totalSpent) * 100 : 0;
        $storagePercentage = $totalSpent > 0 ? (($packages->total_storage ?? 0) / $totalSpent) * 100 : 0;
        $deliveryPercentage = $totalSpent > 0 ? (($packages->total_delivery ?? 0) / $totalSpent) * 100 : 0;

        return [
            'total_packages' => $packages->total_packages ?? 0,
            'total_spent' => round($totalSpent, 2),
            'breakdown' => [
                'freight' => round($packages->total_freight ?? 0, 2),
                'customs' => round($packages->total_customs ?? 0, 2),
                'storage' => round($packages->total_storage ?? 0, 2),
                'delivery' => round($packages->total_delivery ?? 0, 2),
            ],
            'cost_percentages' => [
                'freight' => round($freightPercentage, 1),
                'customs' => round($customsPercentage, 1),
                'storage' => round($storagePercentage, 1),
                'delivery' => round($deliveryPercentage, 1),
            ],
            'averages' => [
                'per_package' => $packages->total_packages > 0 ? round($totalSpent / $packages->total_packages, 2) : 0,
                'freight' => round($packages->avg_freight ?? 0, 2),
                'customs' => round($packages->avg_customs ?? 0, 2),
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
                COALESCE(SUM(customs_duty), 0) as customs_total,
                COALESCE(SUM(storage_fee), 0) as storage_total,
                COALESCE(SUM(delivery_fee), 0) as delivery_total,
                COUNT(*) as package_count
            ')
            ->first();

        $grandTotal = ($categoryTotals->freight_total ?? 0) + 
                     ($categoryTotals->customs_total ?? 0) + 
                     ($categoryTotals->storage_total ?? 0) + 
                     ($categoryTotals->delivery_total ?? 0);

        return [
            'freight' => [
                'total' => round($categoryTotals->freight_total ?? 0, 2),
                'percentage' => $grandTotal > 0 ? round((($categoryTotals->freight_total ?? 0) / $grandTotal) * 100, 1) : 0,
                'average_per_package' => $categoryTotals->package_count > 0 ? round(($categoryTotals->freight_total ?? 0) / $categoryTotals->package_count, 2) : 0,
            ],
            'customs' => [
                'total' => round($categoryTotals->customs_total ?? 0, 2),
                'percentage' => $grandTotal > 0 ? round((($categoryTotals->customs_total ?? 0) / $grandTotal) * 100, 1) : 0,
                'average_per_package' => $categoryTotals->package_count > 0 ? round(($categoryTotals->customs_total ?? 0) / $categoryTotals->package_count, 2) : 0,
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
                COALESCE(AVG(freight_price + customs_duty + storage_fee + delivery_fee), 0) as avg_total_cost,
                COALESCE(AVG(freight_price), 0) as avg_freight,
                COALESCE(AVG(customs_duty), 0) as avg_customs,
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
                'customs' => round($stats->avg_customs ?? 0, 2),
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
        
        // Get monthly spending data
        $monthlyData = $this->packages()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                strftime("%Y", created_at) as year,
                strftime("%m", created_at) as month,
                COUNT(*) as package_count,
                COALESCE(SUM(freight_price + customs_duty + storage_fee + delivery_fee), 0) as total_spent,
                COALESCE(AVG(freight_price + customs_duty + storage_fee + delivery_fee), 0) as avg_cost_per_package
            ')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

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
        // Load role relationship if not already loaded
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        // Return false if user has no role assigned or role relationship is null
        if (!$this->role) {
            return false;
        }

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

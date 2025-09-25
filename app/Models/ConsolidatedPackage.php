<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\PackageStatus;

class ConsolidatedPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'consolidated_tracking_number',
        'customer_id',
        'created_by',
        'total_weight',
        'total_quantity',
        'total_freight_price',
        'total_clearance_fee',
        'total_storage_fee',
        'total_delivery_fee',
        'status',
        'consolidated_at',
        'unconsolidated_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'total_weight' => 'decimal:2',
        'total_quantity' => 'integer',
        'total_freight_price' => 'decimal:2',
        'total_clearance_fee' => 'decimal:2',
        'total_storage_fee' => 'decimal:2',
        'total_delivery_fee' => 'decimal:2',
        'consolidated_at' => 'datetime',
        'unconsolidated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to individual packages
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Relationship to individual packages with eager loading optimization
     */
    public function packagesWithDetails(): HasMany
    {
        return $this->hasMany(Package::class)
            ->select(['id', 'consolidated_package_id', 'tracking_number', 'description', 'weight', 'status', 'freight_price', 'clearance_fee', 'storage_fee', 'delivery_fee'])
            ->orderBy('tracking_number');
    }

    /**
     * Relationship to customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Relationship to admin who created the consolidation
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to consolidation history
     */
    public function history(): HasMany
    {
        return $this->hasMany(ConsolidationHistory::class);
    }

    /**
     * Calculate total weight from individual packages
     */
    public function getTotalWeightAttribute($value)
    {
        if ($value !== null && $value > 0) {
            return $value;
        }

        return $this->packages()->sum('weight') ?? 0;
    }

    /**
     * Calculate total quantity from individual packages
     */
    public function getTotalQuantityAttribute($value)
    {
        if ($value !== null && $value > 0) {
            return $value;
        }

        return $this->packages()->count() ?? 0;
    }

    /**
     * Calculate total cost from all fees
     */
    public function getTotalCostAttribute()
    {
        return ($this->total_freight_price ?? 0) +
               ($this->total_clearance_fee ?? 0) +
               ($this->total_storage_fee ?? 0) +
               ($this->total_delivery_fee ?? 0);
    }

    /**
     * Get formatted tracking numbers of individual packages
     */
    public function getFormattedTrackingNumbersAttribute()
    {
        return $this->packages()->pluck('tracking_number')->implode(', ');
    }

    /**
     * Calculate and update totals from individual packages
     */
    public function calculateTotals(): void
    {
        $packages = $this->packages;

        $this->update([
            'total_weight' => $packages->sum('weight'),
            'total_quantity' => $packages->count(),
            'total_freight_price' => $packages->sum('freight_price'),
            'total_clearance_fee' => $packages->sum('clearance_fee'),
            'total_storage_fee' => $packages->sum('storage_fee'),
            'total_delivery_fee' => $packages->sum('delivery_fee'),
        ]);
    }

    /**
     * Check if consolidated package can be unconsolidated
     */
    public function canBeUnconsolidated(): bool
    {
        // Cannot unconsolidate if not active
        if (!$this->is_active) {
            return false;
        }

        // Cannot unconsolidate if packages are in distribution process
        $packagesInDistribution = $this->packages()
            ->whereIn('status', [PackageStatus::DELIVERED])
            ->exists();

        return !$packagesInDistribution;
    }

    /**
     * Generate consolidated tracking number
     */
    public function generateConsolidatedTrackingNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = static::whereDate('created_at', now()->toDateString())->count() + 1;
        
        return sprintf('CONS-%s-%04d', $date, $sequence);
    }

    /**
     * Update status based on individual package statuses
     */
    public function updateStatusFromPackages(): void
    {
        $packageStatuses = $this->packages()->pluck('status')->unique();

        // If all packages have the same status, use that status
        if ($packageStatuses->count() === 1) {
            $this->update(['status' => $packageStatuses->first()]);
            return;
        }

        // Determine consolidated status based on package statuses
        $statusPriority = [
            'delivered' => 6,
            'ready' => 5,
            'customs' => 4,
            'shipped' => 3,
            'processing' => 2,
            'pending' => 1,
        ];

        $highestPriorityStatus = $packageStatuses
            ->sortByDesc(function ($status) use ($statusPriority) {
                $statusString = is_string($status) ? $status : (string) $status;
                return $statusPriority[$statusString] ?? 0;
            })
            ->first();

        $this->update(['status' => $highestPriorityStatus]);
    }

    /**
     * Synchronize status to all individual packages
     */
    public function syncPackageStatuses($newStatus, ?\App\Models\User $user = null): void
    {
        // Handle both string and PackageStatus enum inputs
        if ($newStatus instanceof \App\Enums\PackageStatus) {
            $statusEnum = $newStatus;
            $statusString = $newStatus->value;
        } else {
            $statusString = $newStatus;
            $statusEnum = \App\Enums\PackageStatus::from($newStatus);
        }
        
        // Update consolidated package status first
        $this->update(['status' => $statusString]);
        
        // Update individual packages using the PackageStatusService to maintain proper logging
        $packageStatusService = app(\App\Services\PackageStatusService::class);
        
        // Get user for logging - prefer passed user, then auth user, then create a system user
        $updateUser = $user ?? auth()->user();
        
        if (!$updateUser) {
            // Try to find a superadmin user
            $updateUser = \App\Models\User::superAdmins()->first();
            
            // If no admin user exists, create a system user for testing
            if (!$updateUser) {
                $updateUser = \App\Models\User::factory()->create([
                    'first_name' => 'System',
                    'last_name' => 'User',
                    'email' => 'system@test.com',
                    'role_id' => 1
                ]);
            }
        }
        
        foreach ($this->packages as $package) {
            // Use fromConsolidatedUpdate = true to bypass the consolidated package check
            // Allow DELIVERED status when synchronizing from consolidated package
            $allowDeliveredStatus = ($statusEnum->value === \App\Enums\PackageStatus::DELIVERED);
            
            $packageStatusService->updateStatus(
                $package, 
                $statusEnum, 
                $updateUser,
                'Status synchronized from consolidated package update',
                $allowDeliveredStatus, // Allow DELIVERED status when consolidation is delivered
                true   // fromConsolidatedUpdate
            );
        }
    }

    /**
     * Scope for active consolidations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to search consolidated packages with optimized eager loading
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function($query) use ($term) {
            $query->where('consolidated_tracking_number', 'like', '%' . $term . '%')
                  ->orWhere('notes', 'like', '%' . $term . '%')
                  ->orWhereHas('packages', function ($packageQuery) use ($term) {
                      $packageQuery->where('tracking_number', 'like', '%' . $term . '%')
                                   ->orWhere('description', 'like', '%' . $term . '%');
                  });
        })->with(['packagesWithDetails', 'customer:id,first_name,last_name,email']);
    }

    /**
     * Scope for optimized loading with essential relationships
     */
    public function scopeWithEssentials($query)
    {
        return $query->with([
            'packagesWithDetails',
            'customer:id,first_name,last_name,email',
            'customer.profile:user_id,account_number',
            'createdBy:id,first_name,last_name,email'
        ]);
    }

    /**
     * Scope for dashboard queries with minimal data
     */
    public function scopeForDashboard($query)
    {
        return $query->select([
            'id', 'consolidated_tracking_number', 'customer_id', 'status', 
            'total_weight', 'total_quantity', 'total_freight_price', 
            'total_clearance_fee', 'total_storage_fee', 'total_delivery_fee',
            'consolidated_at', 'is_active'
        ])->with('customer:id,first_name,last_name,email');
    }

    /**
     * Get search match details for highlighting (optimized)
     */
    public function getSearchMatchDetails($term): array
    {
        $matches = [];
        $searchTerm = strtolower($term);

        // Check consolidated tracking number match
        if (str_contains(strtolower($this->consolidated_tracking_number), $searchTerm)) {
            $matches[] = [
                'field' => 'consolidated_tracking_number',
                'value' => $this->consolidated_tracking_number,
                'type' => 'exact'
            ];
        }

        // Check notes match
        if ($this->notes && str_contains(strtolower($this->notes), $searchTerm)) {
            $matches[] = [
                'field' => 'notes',
                'value' => $this->notes,
                'type' => 'partial'
            ];
        }

        // Check individual package matches with optimized query
        $matchingPackages = $this->packages()
            ->select(['id', 'tracking_number', 'description'])
            ->where(function($query) use ($term) {
                $query->where('tracking_number', 'like', '%' . $term . '%')
                      ->orWhere('description', 'like', '%' . $term . '%');
            })
            ->limit(10) // Limit to prevent performance issues
            ->get();

        foreach ($matchingPackages as $package) {
            if (str_contains(strtolower($package->tracking_number), $searchTerm)) {
                $matches[] = [
                    'field' => 'individual_tracking_number',
                    'value' => $package->tracking_number,
                    'type' => 'individual_package',
                    'package_id' => $package->id
                ];
            }

            if (str_contains(strtolower($package->description), $searchTerm)) {
                $matches[] = [
                    'field' => 'individual_description',
                    'value' => $package->description,
                    'type' => 'individual_package',
                    'package_id' => $package->id
                ];
            }
        }

        return $matches;
    }

    /**
     * Get matching individual packages for a search term (optimized)
     */
    public function getMatchingPackages($term)
    {
        return $this->packages()
            ->select(['id', 'tracking_number', 'description', 'weight', 'status'])
            ->where(function($query) use ($term) {
                $query->where('tracking_number', 'like', '%' . $term . '%')
                      ->orWhere('description', 'like', '%' . $term . '%');
            })
            ->orderBy('tracking_number')
            ->get();
    }

    /**
     * Optimized search scope with full-text capabilities
     */
    public function scopeSearchOptimized($query, $term)
    {
        return $query->where(function($query) use ($term) {
            // Direct matches on consolidated package
            $query->where('consolidated_tracking_number', 'like', '%' . $term . '%')
                  ->orWhere('notes', 'like', '%' . $term . '%');
            
            // Subquery for package matches to avoid N+1 issues
            $query->orWhereExists(function ($subquery) use ($term) {
                $subquery->select(\DB::raw(1))
                         ->from('packages')
                         ->whereColumn('packages.consolidated_package_id', 'consolidated_packages.id')
                         ->where(function($packageQuery) use ($term) {
                             $packageQuery->where('tracking_number', 'like', '%' . $term . '%')
                                          ->orWhere('description', 'like', '%' . $term . '%');
                         });
            });
        })
        ->with(['packagesWithDetails' => function($query) use ($term) {
            // Only load matching packages to reduce memory usage
            $query->where(function($packageQuery) use ($term) {
                $packageQuery->where('tracking_number', 'like', '%' . $term . '%')
                             ->orWhere('description', 'like', '%' . $term . '%');
            });
        }, 'customer:id,name,email']);
    }

    /**
     * Set consolidated tracking number attribute with automatic uppercase conversion
     */
    public function setConsolidatedTrackingNumberAttribute($value)
    {
        // Convert consolidated tracking number to uppercase and trim whitespace
        $this->attributes['consolidated_tracking_number'] = $value ? strtoupper(trim($value)) : $value;
    }
}
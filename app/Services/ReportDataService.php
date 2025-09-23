<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Manifest;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReportDataService
{
    protected $cachePrefix = 'report_data';
    protected $defaultCacheTtl = 900; // 15 minutes
    protected ReportDataFilterService $filterService;
    protected ReportPrivacyService $privacyService;
    protected ReportQueryOptimizationService $queryOptimizationService;

    public function __construct(
        ReportDataFilterService $filterService,
        ReportPrivacyService $privacyService,
        ReportQueryOptimizationService $queryOptimizationService
    ) {
        $this->filterService = $filterService;
        $this->privacyService = $privacyService;
        $this->queryOptimizationService = $queryOptimizationService;
    }

    /**
     * Get sales and collections data with optimized queries
     */
    public function getSalesCollectionsData(array $filters = [], ?User $user = null): array
    {
        // Skip caching for search/sort operations to ensure real-time results
        if (isset($filters['search']) || isset($filters['sort_field'])) {
            return $this->fetchSalesCollectionsData($filters, $user);
        }
        
        $cacheKey = $this->generateCacheKey('sales_collections', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters, $user) {
            return $this->fetchSalesCollectionsData($filters, $user);
        });
    }

    /**
     * Fetch sales and collections data from database
     */
    protected function fetchSalesCollectionsData(array $filters, ?User $user = null): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now();
        $manifestIds = $filters['manifest_ids'] ?? null;
        $officeIds = $filters['office_ids'] ?? null;
        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'shipment_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        // Optimized query for manifest revenue data
        $manifestQuery = DB::table('manifests as m')
            ->select([
                'm.id as id',
                'm.id as manifest_id',
                'm.name as manifest_number',
                'm.type as manifest_type',
                'm.shipment_date as created_at',
                'o.name as office_name',
                DB::raw('COUNT(p.id) as total_packages'),
                DB::raw('SUM(COALESCE(p.freight_price, 0) + COALESCE(p.clearance_fee, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)) as total_owed'),
                DB::raw('SUM(CASE WHEN p.status = "delivered" THEN 1 ELSE 0 END) as delivered_count')
            ])
            ->leftJoin('packages as p', 'm.id', '=', 'p.manifest_id')
            ->leftJoin('offices as o', 'p.office_id', '=', 'o.id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $manifestQuery->whereIn('m.id', $manifestIds);
        }

        if ($officeIds) {
            $manifestQuery->whereIn('p.office_id', $officeIds);
        }

        // Apply search filter
        if ($search) {
            $manifestQuery->where(function($query) use ($search) {
                $query->where('m.name', 'LIKE', "%{$search}%")
                      ->orWhere('m.type', 'LIKE', "%{$search}%")
                      ->orWhere('o.name', 'LIKE', "%{$search}%");
            });
        }

        $manifestQuery->groupBy('m.id', 'm.name', 'm.type', 'm.shipment_date', 'o.name');

        // Apply sorting
        $validSortFields = [
            'manifest_number' => 'm.name',
            'manifest_type' => 'm.type',
            'office_name' => 'o.name',
            'total_packages' => 'total_packages',
            'total_owed' => 'total_owed',
            'created_at' => 'm.shipment_date'
        ];

        if (isset($validSortFields[$sortField])) {
            $manifestQuery->orderBy($validSortFields[$sortField], $sortDirection);
        } else {
            $manifestQuery->orderBy('m.shipment_date', 'desc');
        }

        // Apply role-based filtering if user is provided
        if ($user && !$user->isSuperAdmin()) {
            // For non-superadmins, apply data filtering
            if ($user->isCustomer()) {
                // Customers can only see manifests containing their packages
                $manifestQuery->whereExists(function($query) use ($user) {
                    $query->select(DB::raw(1))
                          ->from('packages as customer_packages')
                          ->whereColumn('customer_packages.manifest_id', 'm.id')
                          ->where('customer_packages.user_id', $user->id);
                });
            }
            // Future: Add office-based filtering for admins
        }

        $manifestData = $manifestQuery->get();

        // Get collected amounts for each manifest
        $manifestIds = $manifestData->pluck('manifest_id')->toArray();
        $collectedAmounts = $this->getCollectedAmountsByManifest($manifestIds);

        // Enhance manifest data with collected amounts
        $enhancedManifestData = $manifestData->map(function ($manifest) use ($collectedAmounts) {
            $collected = $collectedAmounts[$manifest->manifest_id] ?? 0;
            $outstanding = $manifest->total_owed - $collected;
            $collectionRate = $manifest->total_owed > 0 ? ($collected / $manifest->total_owed) * 100 : 0;

            return [
                'id' => $manifest->id,
                'manifest_id' => $manifest->manifest_id,
                'manifest_number' => $manifest->manifest_number,
                'manifest_type' => ucfirst($manifest->manifest_type),
                'office_name' => $manifest->office_name ?? 'N/A',
                'total_packages' => $manifest->total_packages,
                'total_owed' => (float) $manifest->total_owed,
                'total_collected' => $collected,
                'outstanding_balance' => $outstanding,
                'collection_rate' => round($collectionRate, 2),
                'created_at' => $manifest->created_at
            ];
        });

        // Apply post-query sorting for calculated fields
        if (in_array($sortField, ['total_collected', 'outstanding_balance', 'collection_rate'])) {
            $enhancedManifestData = $enhancedManifestData->sortBy($sortField, SORT_REGULAR, $sortDirection === 'desc');
        }

        $result = $enhancedManifestData->values()->toArray();

        // Apply privacy protection if user is provided
        if ($user) {
            $result = $this->privacyService->applyPrivacyProtection($result, $user);
        }

        return $result;
    }

    /**
     * Get collected amounts by manifest using optimized query
     */
    protected function getCollectedAmountsByManifest(array $manifestIds): array
    {
        return $this->queryOptimizationService->getCollectedAmountsByManifest($manifestIds);
    }

    /**
     * Get manifest metrics with optimized queries
     */
    public function getManifestMetrics(array $filters = []): array
    {
        // Skip caching for search/sort operations to ensure real-time results
        if (isset($filters['search']) || isset($filters['sort_field'])) {
            return $this->fetchManifestMetrics($filters);
        }
        
        $cacheKey = $this->generateCacheKey('manifest_metrics', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchManifestMetrics($filters);
        });
    }

    /**
     * Fetch manifest metrics from database
     */
    protected function fetchManifestMetrics(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now();
        $manifestType = $filters['manifest_type'] ?? null;
        $officeIds = $filters['office_ids'] ?? null;
        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $query = DB::table('manifests as m')
            ->select([
                'm.id as id',
                'm.id as manifest_id',
                'm.name as manifest_number',
                'm.type as manifest_type',
                'm.shipment_date as created_at',
                'o.name as office_name',
                DB::raw('COUNT(p.id) as package_count'),
                DB::raw('SUM(COALESCE(p.weight, 0)) as total_weight'),
                DB::raw('SUM(COALESCE(p.cubic_feet, 0)) as total_volume_stored'),
                DB::raw('SUM(CASE WHEN p.status = "delivered" THEN 1 ELSE 0 END) as delivered_count'),
                DB::raw('SUM(CASE WHEN p.status IN ("shipped", "customs") THEN 1 ELSE 0 END) as in_transit_count'),
                DB::raw('SUM(CASE WHEN p.status = "pending" THEN 1 ELSE 0 END) as pending_count'),
                DB::raw('CASE 
                    WHEN COUNT(p.id) = 0 THEN "empty"
                    WHEN SUM(CASE WHEN p.status = "delivered" THEN 1 ELSE 0 END) = COUNT(p.id) THEN "completed"
                    WHEN SUM(CASE WHEN p.status IN ("shipped", "customs") THEN 1 ELSE 0 END) > 0 THEN "processing"
                    ELSE "pending"
                END as status')
            ])
            ->leftJoin('packages as p', 'm.id', '=', 'p.manifest_id')
            ->leftJoin('offices as o', 'p.office_id', '=', 'o.id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestType) {
            $query->where('m.type', $manifestType);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('m.name', 'LIKE', "%{$search}%")
                  ->orWhere('m.type', 'LIKE', "%{$search}%")
                  ->orWhere('o.name', 'LIKE', "%{$search}%");
            });
        }

        $query->groupBy('m.id', 'm.name', 'm.type', 'm.shipment_date', 'o.name');

        // Apply sorting
        $validSortFields = [
            'manifest_number' => 'm.name',
            'manifest_type' => 'm.type',
            'office_name' => 'o.name',
            'package_count' => 'package_count',
            'total_weight' => 'total_weight',
            'created_at' => 'm.shipment_date',
            'status' => 'status'
        ];

        if (isset($validSortFields[$sortField])) {
            $query->orderBy($validSortFields[$sortField], $sortDirection);
        } else {
            $query->orderBy('m.shipment_date', 'desc');
        }

        $metrics = $query->get();

        // Calculate additional metrics
        $enhancedMetrics = $metrics->map(function ($metric) {
            // Calculate volume from dimensions for packages without stored cubic_feet
            $calculatedVolume = $this->calculateVolumeForManifest($metric->manifest_id);
            $totalVolume = max($metric->total_volume_stored, $calculatedVolume);

            $completionRate = $metric->package_count > 0 ? 
                ($metric->delivered_count / $metric->package_count) * 100 : 0;

            // Calculate processing time (simplified - could be enhanced with actual timestamps)
            $processingTime = $this->calculateProcessingTime($metric->manifest_id);
            $efficiencyScore = $this->calculateEfficiencyScore($metric->package_count, $processingTime, $completionRate);

            return [
                'id' => $metric->id,
                'manifest_id' => $metric->manifest_id,
                'manifest_number' => $metric->manifest_number,
                'manifest_type' => ucfirst($metric->manifest_type),
                'office_name' => $metric->office_name ?? 'N/A',
                'package_count' => $metric->package_count,
                'total_weight' => (float) $metric->total_weight,
                'total_volume' => $totalVolume,
                'processing_time' => $processingTime,
                'efficiency_score' => $efficiencyScore,
                'status' => $metric->status,
                'created_at' => $metric->created_at
            ];
        });

        // Apply post-query sorting for calculated fields
        if (in_array($sortField, ['total_volume', 'processing_time', 'efficiency_score'])) {
            $enhancedMetrics = $enhancedMetrics->sortBy($sortField, SORT_REGULAR, $sortDirection === 'desc');
        }

        return $enhancedMetrics->values()->toArray();
    }

    /**
     * Calculate volume for manifest from package dimensions
     */
    protected function calculateVolumeForManifest(int $manifestId): float
    {
        $packages = DB::table('packages')
            ->select(['length_inches', 'width_inches', 'height_inches', 'cubic_feet'])
            ->where('manifest_id', $manifestId)
            ->whereNotNull('length_inches')
            ->whereNotNull('width_inches')
            ->whereNotNull('height_inches')
            ->get();

        $totalVolume = 0;
        foreach ($packages as $package) {
            if ($package->cubic_feet && $package->cubic_feet > 0) {
                $totalVolume += $package->cubic_feet;
            } else {
                // Calculate from dimensions: (L × W × H) ÷ 1728
                $volume = ($package->length_inches * $package->width_inches * $package->height_inches) / 1728;
                $totalVolume += $volume;
            }
        }

        return round($totalVolume, 3);
    }

    /**
     * Calculate processing time for manifest
     */
    protected function calculateProcessingTime(int $manifestId): string
    {
        $manifest = DB::table('manifests')
            ->select(['created_at', 'shipment_date'])
            ->where('id', $manifestId)
            ->first();

        if (!$manifest || !$manifest->shipment_date) {
            return 'N/A';
        }

        $createdAt = Carbon::parse($manifest->created_at);
        $shippedAt = Carbon::parse($manifest->shipment_date);
        
        $diffInDays = $createdAt->diffInDays($shippedAt);
        
        if ($diffInDays == 0) {
            return 'Same day';
        } elseif ($diffInDays == 1) {
            return '1 day';
        } else {
            return "{$diffInDays} days";
        }
    }

    /**
     * Calculate efficiency score based on various factors
     */
    protected function calculateEfficiencyScore(int $packageCount, string $processingTime, float $completionRate): float
    {
        if ($packageCount == 0) {
            return 0;
        }

        // Base score from completion rate
        $score = $completionRate;

        // Adjust based on processing time
        if ($processingTime === 'Same day') {
            $score += 10; // Bonus for same-day processing
        } elseif ($processingTime === '1 day') {
            $score += 5; // Bonus for next-day processing
        } elseif (str_contains($processingTime, 'days')) {
            $days = (int) explode(' ', $processingTime)[0];
            if ($days <= 3) {
                $score += 2; // Small bonus for quick processing
            } elseif ($days > 7) {
                $score -= 10; // Penalty for slow processing
            }
        }

        // Adjust based on package volume (higher volume = more complex)
        if ($packageCount > 100) {
            $score += 5; // Bonus for handling large volumes efficiently
        } elseif ($packageCount < 10) {
            $score -= 2; // Small penalty for low volume (less efficiency opportunity)
        }

        // Ensure score is within 0-100 range
        return max(0, min(100, round($score, 1)));
    }

    /**
     * Get customer statistics with optimized queries
     */
    public function getCustomerStatistics(array $filters = [], ?User $user = null): array
    {
        // Skip caching for search/sort operations to ensure real-time results
        if (isset($filters['search']) || isset($filters['sort_field'])) {
            return $this->fetchCustomerStatistics($filters, $user);
        }
        
        $cacheKey = $this->generateCacheKey('customer_statistics', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters, $user) {
            return $this->fetchCustomerStatistics($filters, $user);
        });
    }

    /**
     * Fetch customer statistics from database
     */
    protected function fetchCustomerStatistics(array $filters, ?User $user = null): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now();
        $customerIds = $filters['customer_ids'] ?? null;
        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'total_spent';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $query = DB::table('users as u')
            ->select([
                'u.id as id',
                'u.id as customer_id',
                DB::raw('CONCAT(u.first_name, " ", u.last_name) as customer_name'),
                'u.email as email',
                'u.account_balance',
                'u.updated_at as last_activity',
                DB::raw('COUNT(p.id) as total_packages'),
                DB::raw('SUM(COALESCE(p.freight_price, 0) + COALESCE(p.clearance_fee, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)) as total_spent'),
                DB::raw('SUM(CASE WHEN p.status = "delivered" THEN 1 ELSE 0 END) as delivered_packages'),
                DB::raw('MAX(p.created_at) as last_package_date'),
                DB::raw('CASE 
                    WHEN u.account_balance < -100 THEN "high_debt"
                    WHEN u.account_balance < 0 THEN "debt"
                    WHEN u.account_balance > 100 THEN "credit"
                    ELSE "balanced"
                END as status')
            ])
            ->leftJoin('packages as p', function($join) use ($dateFrom, $dateTo) {
                $join->on('u.id', '=', 'p.user_id')
                     ->whereBetween('p.created_at', [$dateFrom, $dateTo]);
            })
            ->whereExists(function($query) use ($dateFrom, $dateTo) {
                $query->select(DB::raw(1))
                      ->from('packages')
                      ->whereRaw('packages.user_id = u.id')
                      ->whereBetween('created_at', [$dateFrom, $dateTo]);
            });

        if ($customerIds) {
            $query->whereIn('u.id', $customerIds);
        }

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('u.first_name', 'LIKE', "%{$search}%")
                  ->orWhere('u.last_name', 'LIKE', "%{$search}%")
                  ->orWhere('u.email', 'LIKE', "%{$search}%")
                  ->orWhereRaw('CONCAT(u.first_name, " ", u.last_name) LIKE ?', ["%{$search}%"]);
            });
        }

        $query->groupBy('u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.account_balance', 'u.updated_at');

        // Apply sorting
        $validSortFields = [
            'customer_name' => 'u.first_name',
            'email' => 'u.email',
            'total_packages' => 'total_packages',
            'account_balance' => 'u.account_balance',
            'total_spent' => 'total_spent',
            'last_activity' => 'u.updated_at',
            'status' => 'status'
        ];

        if (isset($validSortFields[$sortField])) {
            $query->orderBy($validSortFields[$sortField], $sortDirection);
        } else {
            $query->orderBy('total_spent', 'desc');
        }

        // Apply role-based filtering if user is provided
        if ($user && !$user->isSuperAdmin()) {
            if ($user->isCustomer()) {
                // Customers can only see their own data
                $query->where('u.id', $user->id);
            }
            // Future: Add office-based filtering for admins
        }

        $customerStats = $query->get();

        // Get payment data for customers
        $customerPayments = $this->getCustomerPayments($customerStats->pluck('customer_id')->toArray(), $dateFrom, $dateTo);

        // Enhance customer data with payment information
        $enhancedStats = $customerStats->map(function ($customer) use ($customerPayments) {
            $payments = $customerPayments[$customer->customer_id] ?? ['total_paid' => 0, 'last_payment_date' => null];
            
            return [
                'id' => $customer->id,
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'email' => $customer->email,
                'account_balance' => (float) $customer->account_balance,
                'total_packages' => $customer->total_packages,
                'total_spent' => (float) $customer->total_spent,
                'last_activity' => $customer->last_activity,
                'status' => $customer->status
            ];
        });

        $result = $enhancedStats->values()->toArray();

        // Apply privacy protection if user is provided
        if ($user) {
            $result = $this->privacyService->applyPrivacyProtection($result, $user);
        }

        return $result;
    }

    /**
     * Get customer payment data
     */
    protected function getCustomerPayments(array $customerIds, $dateFrom, $dateTo): array
    {
        if (empty($customerIds)) {
            return [];
        }

        $payments = DB::table('customer_transactions')
            ->select([
                'user_id',
                DB::raw('SUM(amount) as total_paid'),
                DB::raw('MAX(created_at) as last_payment_date')
            ])
            ->whereIn('user_id', $customerIds)
            ->where('type', CustomerTransaction::TYPE_PAYMENT)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('user_id')
            ->get();

        return $payments->mapWithKeys(function ($payment) {
            return [
                $payment->user_id => [
                    'total_paid' => (float) $payment->total_paid,
                    'last_payment_date' => $payment->last_payment_date
                ]
            ];
        })->toArray();
    }

    /**
     * Get financial breakdown with optimized aggregation
     */
    public function getFinancialBreakdown(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('financial_breakdown', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchFinancialBreakdown($filters);
        });
    }

    /**
     * Fetch financial breakdown from database
     */
    protected function fetchFinancialBreakdown(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
        $dateTo = $filters['date_to'] ?? Carbon::now();

        // Revenue breakdown by service type
        $revenueBreakdown = DB::table('packages')
            ->selectRaw('
                SUM(COALESCE(freight_price, 0)) as freight_revenue,
                SUM(COALESCE(clearance_fee, 0)) as clearance_revenue,
                SUM(COALESCE(storage_fee, 0)) as storage_revenue,
                SUM(COALESCE(delivery_fee, 0)) as delivery_revenue,
                COUNT(*) as package_count
            ')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->first();

        // Collections data
        $collections = DB::table('customer_transactions')
            ->selectRaw('
                SUM(amount) as total_collected,
                COUNT(*) as payment_count,
                AVG(amount) as average_payment
            ')
            ->where('type', CustomerTransaction::TYPE_PAYMENT)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->first();

        // Outstanding balances
        $outstanding = DB::table('users')
            ->selectRaw('
                SUM(ABS(account_balance)) as total_outstanding,
                COUNT(*) as customers_with_debt
            ')
            ->where('account_balance', '<', 0)
            ->first();

        // Daily revenue trend
        $dailyRevenue = DB::table('packages')
            ->selectRaw('
                DATE(created_at) as date,
                SUM(COALESCE(freight_price, 0) + COALESCE(clearance_fee, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)) as daily_revenue,
                COUNT(*) as daily_packages
            ')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return [
            'revenue_breakdown' => [
                'freight_revenue' => (float) ($revenueBreakdown->freight_revenue ?? 0),
                'clearance_revenue' => (float) ($revenueBreakdown->clearance_revenue ?? 0),
                'storage_revenue' => (float) ($revenueBreakdown->storage_revenue ?? 0),
                'delivery_revenue' => (float) ($revenueBreakdown->delivery_revenue ?? 0),
                'total_revenue' => (float) (
                    ($revenueBreakdown->freight_revenue ?? 0) + 
                    ($revenueBreakdown->clearance_revenue ?? 0) + 
                    ($revenueBreakdown->storage_revenue ?? 0) + 
                    ($revenueBreakdown->delivery_revenue ?? 0)
                ),
                'package_count' => $revenueBreakdown->package_count ?? 0
            ],
            'collections' => [
                'total_collected' => (float) ($collections->total_collected ?? 0),
                'payment_count' => $collections->payment_count ?? 0,
                'average_payment' => (float) ($collections->average_payment ?? 0)
            ],
            'outstanding' => [
                'total_outstanding' => (float) ($outstanding->total_outstanding ?? 0),
                'customers_with_debt' => $outstanding->customers_with_debt ?? 0
            ],
            'daily_trends' => $dailyRevenue->map(function ($day) {
                return [
                    'date' => $day->date,
                    'revenue' => (float) $day->daily_revenue,
                    'packages' => $day->daily_packages
                ];
            })->toArray(),
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'generated_at' => now()
        ];
    }

    /**
     * Calculate summary for sales collections data
     */
    protected function calculateDataSummary($manifestData): array
    {
        $totalOwed = $manifestData->sum('total_owed');
        $totalCollected = $manifestData->sum('total_collected');
        $totalOutstanding = $manifestData->sum('outstanding_balance');

        return [
            'total_manifests' => $manifestData->count(),
            'total_packages' => $manifestData->sum('package_count'),
            'delivered_packages' => $manifestData->sum('delivered_count'),
            'total_revenue_owed' => $totalOwed,
            'total_revenue_collected' => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'overall_collection_rate' => $totalOwed > 0 ? round(($totalCollected / $totalOwed) * 100, 2) : 0,
            'delivery_rate' => $manifestData->sum('package_count') > 0 ? 
                round(($manifestData->sum('delivered_count') / $manifestData->sum('package_count')) * 100, 2) : 0
        ];
    }

    /**
     * Calculate summary for manifest metrics
     */
    protected function calculateManifestSummary($metricsData): array
    {
        return [
            'total_manifests' => $metricsData->count(),
            'total_packages' => $metricsData->sum('package_count'),
            'total_weight' => $metricsData->sum('total_weight'),
            'total_volume' => $metricsData->sum('total_volume'),
            'average_completion_rate' => $metricsData->count() > 0 ? 
                round($metricsData->avg('completion_rate'), 2) : 0,
            'air_manifests' => $metricsData->where('manifest_type', 'air')->count(),
            'sea_manifests' => $metricsData->where('manifest_type', 'sea')->count()
        ];
    }

    /**
     * Calculate summary for customer statistics
     */
    protected function calculateCustomerSummary($customerData): array
    {
        return [
            'total_customers' => $customerData->count(),
            'total_packages' => $customerData->sum('package_count'),
            'total_charges' => $customerData->sum('total_charges'),
            'total_payments' => $customerData->sum('total_paid'),
            'customers_with_debt' => $customerData->where('account_balance', '<', 0)->count(),
            'average_customer_value' => $customerData->count() > 0 ? 
                round($customerData->sum('total_charges') / $customerData->count(), 2) : 0
        ];
    }

    /**
     * Generate cache key for data
     */
    protected function generateCacheKey(string $type, array $filters): string
    {
        $filterHash = md5(serialize($filters));
        return "{$this->cachePrefix}:{$type}:{$filterHash}";
    }

    /**
     * Invalidate report data cache
     */
    public function invalidateCache(string $pattern = null): void
    {
        if ($pattern) {
            $keys = Cache::getRedis()->keys("{$this->cachePrefix}:{$pattern}:*");
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } else {
            // Clear all report data cache
            $keys = Cache::getRedis()->keys("{$this->cachePrefix}:*");
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }

    /**
     * Warm up frequently accessed report data
     */
    public function warmUpCache(array $commonFilters = []): void
    {
        // Common filter combinations to pre-cache
        $defaultFilters = [
            ['date_from' => Carbon::now()->subDays(7), 'date_to' => Carbon::now()], // Last 7 days
            ['date_from' => Carbon::now()->subDays(30), 'date_to' => Carbon::now()], // Last 30 days
            ['date_from' => Carbon::now()->subDays(90), 'date_to' => Carbon::now()], // Last 90 days
        ];

        $filtersToCache = array_merge($defaultFilters, $commonFilters);

        foreach ($filtersToCache as $filters) {
            // Pre-cache sales collections data
            $this->getSalesCollectionsData($filters);
            
            // Pre-cache manifest metrics
            $this->getManifestMetrics($filters);
            
            // Pre-cache financial breakdown
            $this->getFinancialBreakdown($filters);
        }
    }
}
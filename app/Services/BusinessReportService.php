<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Manifest;
use App\Models\CustomerTransaction;
use App\Models\User;
use App\Traits\HandlesReportErrors;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessReportService
{
    use HandlesReportErrors;
    /**
     * Generate sales and collections report data
     */
    public function generateSalesCollectionsReport(array $filters = []): array
    {
        return $this->executeWithErrorHandling(function() use ($filters) {
            // Validate filters and permissions
            $this->validateReportFilters($filters, 'sales');
            $this->checkReportPermissions('sales', $filters);
            $this->checkResourceLimits($filters, 'sales');
            
            // Log report access
            $this->logReportAccess('sales', $filters);

            $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
            $dateTo = $filters['date_to'] ?? Carbon::now();
            $manifestIds = $filters['manifest_ids'] ?? null;
            $officeIds = $filters['office_ids'] ?? null;

            // Get manifest-based revenue data
            $manifestData = $this->getManifestRevenueData($dateFrom, $dateTo, $manifestIds, $officeIds);
            
            // Get collections data
            $collectionsData = $this->getCollectionsData($dateFrom, $dateTo, $manifestIds, $officeIds);
            
            // Get outstanding balances
            $outstandingData = $this->getOutstandingBalancesData($manifestIds, $officeIds);

            return [
                'manifests' => $manifestData,
                'collections' => $collectionsData,
                'outstanding' => $outstandingData,
                'summary' => $this->calculateSalesSummary($manifestData, $collectionsData, $outstandingData),
                'filters_applied' => $filters,
                'generated_at' => now()
            ];
        }, 'sales', $filters);
    }

    /**
     * Get manifest-based revenue calculation data
     */
    protected function getManifestRevenueData($dateFrom, $dateTo, $manifestIds = null, $officeIds = null): array
    {
        $query = Manifest::query()
            ->with(['packages' => function($q) use ($officeIds) {
                $q->select([
                    'id', 'manifest_id', 'user_id', 'office_id', 'tracking_number',
                    'freight_price', 'clearance_fee', 'storage_fee', 'delivery_fee', 'status'
                ]);
                if ($officeIds) {
                    $q->whereIn('office_id', $officeIds);
                }
            }])
            ->whereBetween('shipment_date', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $query->whereIn('id', $manifestIds);
        }

        $manifests = $query->get();

        return $manifests->map(function ($manifest) {
            $packages = $manifest->packages;
            
            $totalOwed = $packages->sum(function ($package) {
                return ($package->freight_price ?? 0) + 
                       ($package->clearance_fee ?? 0) + 
                       ($package->storage_fee ?? 0) + 
                       ($package->delivery_fee ?? 0);
            });

            // Calculate actual collections (from distributions)
            $actualCollected = DB::table('package_distribution_items as pdi')
                ->join('packages as p', 'pdi.package_id', '=', 'p.id')
                ->where('p.manifest_id', $manifest->id)
                ->sum('pdi.total_cost') ?? 0;
            
            // Calculate write-offs for this manifest
            $totalWriteOffs = DB::table('customer_transactions')
                ->where('type', 'write_off')
                ->where('manifest_id', $manifest->id)
                ->sum('amount') ?? 0;
            
            // Total collected includes both actual collections and write-offs
            $totalCollected = $actualCollected + $totalWriteOffs;
            
            $packageCount = $packages->count();
            $deliveredCount = $packages->where('status', 'delivered')->count();

            return [
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'manifest_type' => $manifest->type,
                'shipment_date' => $manifest->shipment_date,
                'package_count' => $packageCount,
                'delivered_count' => $deliveredCount,
                'total_owed' => $totalOwed,
                'total_collected' => $actualCollected,
                'total_write_offs' => $totalWriteOffs,
                'outstanding_balance' => $totalOwed - $totalCollected,
                'collection_rate' => $totalOwed > 0 ? ($totalCollected / $totalOwed) * 100 : 0,
                'packages' => $packages->map(function ($package) {
                    return [
                        'id' => $package->id,
                        'tracking_number' => $package->tracking_number,
                        'user_id' => $package->user_id,
                        'status' => $package->status,
                        'total_charges' => ($package->freight_price ?? 0) + 
                                         ($package->clearance_fee ?? 0) + 
                                         ($package->storage_fee ?? 0) + 
                                         ($package->delivery_fee ?? 0),
                        'freight_price' => $package->freight_price ?? 0,
                        'clearance_fee' => $package->clearance_fee ?? 0,
                        'storage_fee' => $package->storage_fee ?? 0,
                        'delivery_fee' => $package->delivery_fee ?? 0,
                    ];
                })
            ];
        })->toArray();
    }

    /**
     * Calculate collected amounts for a specific manifest
     */
    protected function calculateCollectedForManifest(int $manifestId): float
    {
        // Get the sum of individual package costs from distribution items for packages in this manifest
        // This correctly accounts for the actual amount collected per package, not the total distribution amount
        $collected = DB::table('package_distribution_items as pdi')
            ->join('packages as p', 'pdi.package_id', '=', 'p.id')
            ->where('p.manifest_id', $manifestId)
            ->sum('pdi.total_cost') ?? 0;
        
        // Add write-offs for this manifest (write-offs reduce the amount owed, so they count as "collected")
        $writeOffs = DB::table('customer_transactions')
            ->where('type', 'write_off')
            ->where('manifest_id', $manifestId)
            ->sum('amount') ?? 0;
        
        return $collected + $writeOffs;
    }

    /**
     * Get collections data with payment analysis
     */
    protected function getCollectionsData($dateFrom, $dateTo, $manifestIds = null, $officeIds = null): array
    {
        // Get collections from package distributions (actual collections tied to specific packages/manifests)
        $distributionQuery = DB::table('package_distributions as pd')
            ->join('package_distribution_items as pdi', 'pd.id', '=', 'pdi.distribution_id')
            ->join('packages as p', 'pdi.package_id', '=', 'p.id')
            ->whereBetween('pd.distributed_at', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $distributionQuery->whereIn('p.manifest_id', $manifestIds);
        }

        if ($officeIds) {
            $distributionQuery->whereIn('p.office_id', $officeIds);
        }

        $distributionCollections = $distributionQuery
            ->selectRaw('
                DATE(pd.distributed_at) as collection_date,
                SUM(pdi.total_cost) as daily_amount,
                COUNT(DISTINCT pd.id) as distribution_count,
                COUNT(DISTINCT pd.customer_id) as unique_customers
            ')
            ->groupBy('collection_date')
            ->orderBy('collection_date', 'desc')
            ->get();
        
        // Get daily write-offs for the same period
        $writeOffQuery = DB::table('customer_transactions')
            ->where('type', 'write_off')
            ->whereBetween('created_at', [$dateFrom, $dateTo]);
        
        if ($manifestIds) {
            $writeOffQuery->whereIn('manifest_id', $manifestIds);
        }
        
        $dailyWriteOffs = $writeOffQuery
            ->selectRaw('
                DATE(created_at) as write_off_date,
                SUM(amount) as daily_write_off_amount,
                COUNT(*) as write_off_count
            ')
            ->groupBy('write_off_date')
            ->orderBy('write_off_date', 'desc')
            ->get();

        // Also get general payment transactions for context
        $paymentQuery = CustomerTransaction::query()
            ->where('type', CustomerTransaction::TYPE_PAYMENT)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->with(['user:id,first_name,last_name,email']);

        // Filter by manifest if specified
        if ($manifestIds) {
            $paymentQuery->whereHas('user.packages', function($q) use ($manifestIds) {
                $q->whereIn('manifest_id', $manifestIds);
            });
        }

        // Filter by office if specified
        if ($officeIds) {
            $paymentQuery->whereHas('user.packages', function($q) use ($officeIds) {
                $q->whereIn('office_id', $officeIds);
            });
        }

        $payments = $paymentQuery->orderBy('created_at', 'desc')->get();

        $dailyCollections = $distributionCollections->map(function($collection) {
            return [
                'date' => $collection->collection_date,
                'total_amount' => $collection->daily_amount,
                'transaction_count' => $collection->distribution_count,
                'unique_customers' => $collection->unique_customers
            ];
        });
        
        $dailyWriteOffData = $dailyWriteOffs->map(function($writeOff) {
            return [
                'date' => $writeOff->write_off_date,
                'total_amount' => $writeOff->daily_write_off_amount,
                'count' => $writeOff->write_off_count
            ];
        });

        return [
            'daily_collections' => $dailyCollections,
            'daily_write_offs' => $dailyWriteOffData,
            'total_collected' => $distributionCollections->sum('daily_amount'),
            'total_write_offs' => $dailyWriteOffs->sum('daily_write_off_amount'),
            'total_transactions' => $distributionCollections->sum('distribution_count'),
            'unique_customers' => $distributionCollections->sum('unique_customers'),
            'average_payment' => $distributionCollections->count() > 0 ? 
                $distributionCollections->sum('daily_amount') / $distributionCollections->sum('distribution_count') : 0,
            'recent_payments' => $payments->take(10)->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'user_name' => $payment->user ? ($payment->user->first_name . ' ' . $payment->user->last_name) : 'Unknown',
                    'amount' => $payment->amount,
                    'created_at' => $payment->created_at,
                    'description' => $payment->description
                ];
            })
        ];
    }

    /**
     * Get outstanding balances data
     */
    protected function getOutstandingBalancesData($manifestIds = null, $officeIds = null): array
    {
        // Get users with outstanding balances
        $query = User::query()
            ->select(['id', 'first_name', 'last_name', 'email', 'account_balance'])
            ->where('account_balance', '<', 0);

        // Filter by manifest if specified
        if ($manifestIds) {
            $query->whereHas('packages', function($q) use ($manifestIds) {
                $q->whereIn('manifest_id', $manifestIds);
            });
        }

        // Filter by office if specified
        if ($officeIds) {
            $query->whereHas('packages', function($q) use ($officeIds) {
                $q->whereIn('office_id', $officeIds);
            });
        }

        $usersWithDebt = $query->orderBy('account_balance', 'asc')->get();

        // Calculate aging buckets
        $agingBuckets = $this->calculateOutstandingAging($usersWithDebt->pluck('id')->toArray());

        return [
            'total_outstanding' => abs($usersWithDebt->sum('account_balance')),
            'customer_count' => $usersWithDebt->count(),
            'largest_debt' => $usersWithDebt->count() > 0 ? abs($usersWithDebt->min('account_balance')) : 0,
            'average_debt' => $usersWithDebt->count() > 0 ? abs($usersWithDebt->sum('account_balance') / $usersWithDebt->count()) : 0,
            'aging_buckets' => $agingBuckets,
            'top_debtors' => $usersWithDebt->take(10)->map(function($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'outstanding_balance' => abs($user->account_balance),
                    'last_payment_date' => $this->getLastPaymentDate($user->id)
                ];
            })
        ];
    }

    /**
     * Calculate outstanding balance aging buckets
     */
    protected function calculateOutstandingAging(array $userIds): array
    {
        if (empty($userIds)) {
            return [
                '0-30_days' => ['amount' => 0, 'count' => 0],
                '31-60_days' => ['amount' => 0, 'count' => 0],
                '61-90_days' => ['amount' => 0, 'count' => 0],
                '90+_days' => ['amount' => 0, 'count' => 0]
            ];
        }

        // Get the oldest unpaid charges for each user
        $oldestCharges = DB::table('customer_transactions')
            ->select(['user_id', DB::raw('MIN(created_at) as oldest_charge_date')])
            ->whereIn('user_id', $userIds)
            ->where('type', CustomerTransaction::TYPE_CHARGE)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $buckets = [
            '0-30_days' => ['amount' => 0, 'count' => 0],
            '31-60_days' => ['amount' => 0, 'count' => 0],
            '61-90_days' => ['amount' => 0, 'count' => 0],
            '90+_days' => ['amount' => 0, 'count' => 0]
        ];

        $users = User::whereIn('id', $userIds)->where('account_balance', '<', 0)->get();

        foreach ($users as $user) {
            $oldestCharge = $oldestCharges->get($user->id);
            if (!$oldestCharge) continue;

            $daysSinceOldest = Carbon::parse($oldestCharge->oldest_charge_date)->diffInDays(now());
            $outstandingAmount = abs($user->account_balance);

            if ($daysSinceOldest <= 30) {
                $buckets['0-30_days']['amount'] += $outstandingAmount;
                $buckets['0-30_days']['count']++;
            } elseif ($daysSinceOldest <= 60) {
                $buckets['31-60_days']['amount'] += $outstandingAmount;
                $buckets['31-60_days']['count']++;
            } elseif ($daysSinceOldest <= 90) {
                $buckets['61-90_days']['amount'] += $outstandingAmount;
                $buckets['61-90_days']['count']++;
            } else {
                $buckets['90+_days']['amount'] += $outstandingAmount;
                $buckets['90+_days']['count']++;
            }
        }

        return $buckets;
    }

    /**
     * Get last payment date for a user
     */
    protected function getLastPaymentDate(int $userId): ?Carbon
    {
        $lastPayment = CustomerTransaction::where('user_id', $userId)
            ->where('type', CustomerTransaction::TYPE_PAYMENT)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastPayment ? $lastPayment->created_at : null;
    }

    /**
     * Calculate sales summary metrics
     */
    protected function calculateSalesSummary(array $manifestData, array $collectionsData, array $outstandingData): array
    {
        $manifestCollection = collect($manifestData);
        
        $totalOwed = $manifestCollection->sum('total_owed');
        $totalCollected = $manifestCollection->sum('total_collected');
        $totalOutstanding = $manifestCollection->sum('outstanding_balance');

        return [
            'total_revenue_owed' => $totalOwed,
            'total_revenue_collected' => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'overall_collection_rate' => $totalOwed > 0 ? ($totalCollected / $totalOwed) * 100 : 0,
            'total_manifests' => $manifestCollection->count(),
            'total_packages' => $manifestCollection->sum('package_count'),
            'delivered_packages' => $manifestCollection->sum('delivered_count'),
            'delivery_rate' => $manifestCollection->sum('package_count') > 0 ? 
                ($manifestCollection->sum('delivered_count') / $manifestCollection->sum('package_count')) * 100 : 0,
            'average_manifest_value' => $manifestCollection->count() > 0 ? $totalOwed / $manifestCollection->count() : 0,
            'average_collection_rate' => $manifestCollection->count() > 0 ? 
                $manifestCollection->avg('collection_rate') : 0,
            'manifests_fully_collected' => $manifestCollection->where('collection_rate', '>=', 100)->count(),
            'manifests_partially_collected' => $manifestCollection->where('collection_rate', '>', 0)->where('collection_rate', '<', 100)->count(),
            'manifests_uncollected' => $manifestCollection->where('collection_rate', '=', 0)->count(),
            'customers_with_debt' => $outstandingData['customer_count'],
            'total_debt_amount' => $outstandingData['total_outstanding']
        ];
    }

    /**
     * Generate manifest performance report
     */
    public function generateManifestPerformanceReport(array $filters = []): array
    {
        return $this->executeWithErrorHandling(function() use ($filters) {
            // Validate filters and permissions
            $this->validateReportFilters($filters, 'manifest');
            $this->checkReportPermissions('manifest', $filters);
            $this->checkResourceLimits($filters, 'manifest');
            
            // Log report access
            $this->logReportAccess('manifest', $filters);

            $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
            $dateTo = $filters['date_to'] ?? Carbon::now();
            $manifestType = $filters['manifest_type'] ?? null;
            $officeIds = $filters['office_ids'] ?? null;

            $query = Manifest::query()
                ->with(['packages' => function($q) use ($officeIds) {
                    $q->select([
                        'id', 'manifest_id', 'weight', 'cubic_feet', 'status', 'created_at',
                        'length_inches', 'width_inches', 'height_inches'
                    ]);
                    if ($officeIds) {
                        $q->whereIn('office_id', $officeIds);
                    }
                }])
                ->whereBetween('shipment_date', [$dateFrom, $dateTo]);

            if ($manifestType) {
                $query->where('type', $manifestType);
            }

            $manifests = $query->get();

            return $manifests->map(function ($manifest) {
                $packages = $manifest->packages;
                $totalWeight = $packages->sum('weight') ?? 0;
                $totalVolume = $packages->sum(function($package) {
                    return $package->getVolumeInCubicFeet();
                });

                // Calculate processing times
                $processingTimes = $packages->map(function($package) {
                    if ($package->status === 'delivered') {
                        return $package->created_at->diffInDays(now());
                    }
                    return null;
                })->filter()->values();

                $avgProcessingTime = $processingTimes->count() > 0 ? $processingTimes->avg() : null;

                return [
                    'manifest_id' => $manifest->id,
                    'manifest_name' => $manifest->name,
                    'manifest_type' => $manifest->type,
                    'shipment_date' => $manifest->shipment_date,
                    'package_count' => $packages->count(),
                    'total_weight' => $totalWeight,
                    'total_volume' => $totalVolume,
                    'average_processing_time_days' => $avgProcessingTime,
                    'delivered_count' => $packages->where('status', 'delivered')->count(),
                    'in_transit_count' => $packages->whereIn('status', ['shipped', 'customs'])->count(),
                    'pending_count' => $packages->where('status', 'pending')->count(),
                    'completion_rate' => $packages->count() > 0 ? 
                        ($packages->where('status', 'delivered')->count() / $packages->count()) * 100 : 0
                ];
            })->toArray();
        }, 'manifest', $filters);
    }

    /**
     * Generate customer analytics report
     */
    public function generateCustomerAnalyticsReport(array $filters = []): array
    {
        return $this->executeWithErrorHandling(function() use ($filters) {
            // Validate filters and permissions
            $this->validateReportFilters($filters, 'customer');
            $this->checkReportPermissions('customer', $filters);
            $this->checkResourceLimits($filters, 'customer');
            
            // Log report access
            $this->logReportAccess('customer', $filters);

            $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
            $dateTo = $filters['date_to'] ?? Carbon::now();
            $customerIds = $filters['customer_ids'] ?? null;

            $query = User::query()
                ->select(['id', 'first_name', 'last_name', 'email', 'account_balance'])
                ->with(['packages' => function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom, $dateTo])
                      ->select(['id', 'user_id', 'freight_price', 'clearance_fee', 'storage_fee', 'delivery_fee', 'status']);
                }, 'transactions' => function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom, $dateTo])
                      ->select(['id', 'user_id', 'type', 'amount', 'created_at']);
                }])
                ->whereHas('packages', function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                });

            if ($customerIds) {
                $query->whereIn('id', $customerIds);
            }

            $customers = $query->get();

            return $customers->map(function ($customer) {
                $packages = $customer->packages;
                $transactions = $customer->transactions;

                $totalSpent = $packages->sum(function($package) {
                    return ($package->freight_price ?? 0) + 
                           ($package->clearance_fee ?? 0) + 
                           ($package->storage_fee ?? 0) + 
                           ($package->delivery_fee ?? 0);
                });

                $totalPaid = $transactions->where('type', CustomerTransaction::TYPE_PAYMENT)->sum('amount');

                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->first_name . ' ' . $customer->last_name,
                    'customer_email' => $customer->email,
                    'account_balance' => $customer->account_balance,
                    'package_count' => $packages->count(),
                    'total_spent' => $totalSpent,
                    'total_paid' => $totalPaid,
                    'outstanding_balance' => $totalSpent - $totalPaid,
                    'delivered_packages' => $packages->where('status', 'delivered')->count(),
                    'average_package_value' => $packages->count() > 0 ? $totalSpent / $packages->count() : 0,
                    'last_package_date' => $packages->max('created_at'),
                    'last_payment_date' => $transactions->where('type', CustomerTransaction::TYPE_PAYMENT)->max('created_at')
                ];
            })->toArray();
        }, 'customer', $filters);
    }

    /**
     * Generate financial summary report
     */
    public function generateFinancialSummaryReport(array $filters = []): array
    {
        return $this->executeWithErrorHandling(function() use ($filters) {
            // Validate filters and permissions
            $this->validateReportFilters($filters, 'financial');
            $this->checkReportPermissions('financial', $filters);
            $this->checkResourceLimits($filters, 'financial');
            
            // Log report access
            $this->logReportAccess('financial', $filters);

            $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonth();
            $dateTo = $filters['date_to'] ?? Carbon::now();

            // Revenue breakdown by service type
            $revenueBreakdown = Package::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    SUM(freight_price) as freight_revenue,
                    SUM(clearance_fee) as clearance_revenue,
                    SUM(storage_fee) as storage_revenue,
                    SUM(delivery_fee) as delivery_revenue,
                    COUNT(*) as package_count
                ')
                ->first();

            // Payment collections
            $collections = CustomerTransaction::where('type', CustomerTransaction::TYPE_PAYMENT)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    SUM(amount) as total_collected,
                    COUNT(*) as payment_count,
                    AVG(amount) as average_payment
                ')
                ->first();

            // Outstanding balances
            $outstanding = User::where('account_balance', '<', 0)
                ->selectRaw('
                    SUM(ABS(account_balance)) as total_outstanding,
                    COUNT(*) as customers_with_debt
                ')
                ->first();

            return [
                'revenue_breakdown' => [
                    'freight_revenue' => $revenueBreakdown->freight_revenue ?? 0,
                    'clearance_revenue' => $revenueBreakdown->clearance_revenue ?? 0,
                    'storage_revenue' => $revenueBreakdown->storage_revenue ?? 0,
                    'delivery_revenue' => $revenueBreakdown->delivery_revenue ?? 0,
                    'total_revenue' => ($revenueBreakdown->freight_revenue ?? 0) + 
                                     ($revenueBreakdown->clearance_revenue ?? 0) + 
                                     ($revenueBreakdown->storage_revenue ?? 0) + 
                                     ($revenueBreakdown->delivery_revenue ?? 0),
                    'package_count' => $revenueBreakdown->package_count ?? 0
                ],
                'collections' => [
                    'total_collected' => $collections->total_collected ?? 0,
                    'payment_count' => $collections->payment_count ?? 0,
                    'average_payment' => $collections->average_payment ?? 0
                ],
                'outstanding' => [
                    'total_outstanding' => $outstanding->total_outstanding ?? 0,
                    'customers_with_debt' => $outstanding->customers_with_debt ?? 0
                ],
                'period' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ],
                'generated_at' => now()
            ];
        }, 'financial', $filters);
    }
}
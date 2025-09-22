<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Manifest;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SalesAnalyticsService
{
    protected $cachePrefix = 'sales_analytics';
    protected $defaultCacheTtl = 1800; // 30 minutes

    /**
     * Calculate collection rates and trend analysis
     */
    public function calculateCollectionRates(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('collection_rates', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchCollectionRates($filters);
        });
    }

    /**
     * Fetch collection rates from database
     */
    protected function fetchCollectionRates(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonths(3);
        $dateTo = $filters['date_to'] ?? Carbon::now();
        $manifestIds = $filters['manifest_ids'] ?? null;
        $officeIds = $filters['office_ids'] ?? null;

        // Monthly collection rates
        $monthlyRates = $this->calculateMonthlyCollectionRates($dateFrom, $dateTo, $manifestIds, $officeIds);
        
        // Weekly collection rates for trend analysis
        $weeklyRates = $this->calculateWeeklyCollectionRates($dateFrom, $dateTo, $manifestIds, $officeIds);
        
        // Collection rates by manifest type
        $ratesByType = $this->calculateCollectionRatesByType($dateFrom, $dateTo, $manifestIds, $officeIds);
        
        // Collection efficiency metrics
        $efficiencyMetrics = $this->calculateCollectionEfficiency($dateFrom, $dateTo, $manifestIds, $officeIds);

        return [
            'monthly_rates' => $monthlyRates,
            'weekly_rates' => $weeklyRates,
            'rates_by_type' => $ratesByType,
            'efficiency_metrics' => $efficiencyMetrics,
            'trend_analysis' => $this->analyzeTrends($monthlyRates, $weeklyRates),
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'generated_at' => now()
        ];
    }

    /**
     * Calculate monthly collection rates
     */
    protected function calculateMonthlyCollectionRates($dateFrom, $dateTo, $manifestIds, $officeIds): array
    {
        $query = DB::table('manifests as m')
            ->select([
                DB::raw('YEAR(m.shipment_date) as year'),
                DB::raw('MONTH(m.shipment_date) as month'),
                DB::raw('SUM(COALESCE(p.freight_price, 0) + COALESCE(p.customs_duty, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)) as total_owed'),
                DB::raw('COUNT(p.id) as package_count'),
                'm.type as manifest_type'
            ])
            ->leftJoin('packages as p', 'm.id', '=', 'p.manifest_id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $query->whereIn('m.id', $manifestIds);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        $monthlyData = $query->groupBy(DB::raw('YEAR(m.shipment_date)'), DB::raw('MONTH(m.shipment_date)'), 'm.type')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Get collected amounts for each month
        $monthlyCollected = $this->getMonthlyCollectedAmounts($dateFrom, $dateTo, $manifestIds, $officeIds);

        // Combine owed and collected data
        $monthlyRates = [];
        foreach ($monthlyData as $data) {
            $monthKey = "{$data->year}-{$data->month}-{$data->manifest_type}";
            $collected = $monthlyCollected[$monthKey] ?? 0;
            $collectionRate = $data->total_owed > 0 ? ($collected / $data->total_owed) * 100 : 0;

            $monthlyRates[] = [
                'year' => $data->year,
                'month' => $data->month,
                'month_name' => Carbon::create($data->year, $data->month)->format('F Y'),
                'manifest_type' => $data->manifest_type,
                'total_owed' => (float) $data->total_owed,
                'total_collected' => $collected,
                'collection_rate' => round($collectionRate, 2),
                'package_count' => $data->package_count,
                'outstanding_balance' => $data->total_owed - $collected
            ];
        }

        return $monthlyRates;
    }

    /**
     * Calculate weekly collection rates for trend analysis
     */
    protected function calculateWeeklyCollectionRates($dateFrom, $dateTo, $manifestIds, $officeIds): array
    {
        $query = DB::table('manifests as m')
            ->select([
                DB::raw('YEARWEEK(m.shipment_date) as year_week'),
                DB::raw('SUM(COALESCE(p.freight_price, 0) + COALESCE(p.customs_duty, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)) as total_owed'),
                DB::raw('COUNT(p.id) as package_count')
            ])
            ->leftJoin('packages as p', 'm.id', '=', 'p.manifest_id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $query->whereIn('m.id', $manifestIds);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        $weeklyData = $query->groupBy(DB::raw('YEARWEEK(m.shipment_date)'))
            ->orderBy('year_week')
            ->get();

        // Get weekly collected amounts
        $weeklyCollected = $this->getWeeklyCollectedAmounts($dateFrom, $dateTo, $manifestIds, $officeIds);

        $weeklyRates = [];
        foreach ($weeklyData as $data) {
            $collected = $weeklyCollected[$data->year_week] ?? 0;
            $collectionRate = $data->total_owed > 0 ? ($collected / $data->total_owed) * 100 : 0;

            $weeklyRates[] = [
                'year_week' => $data->year_week,
                'week_start' => $this->getWeekStartDate($data->year_week),
                'total_owed' => (float) $data->total_owed,
                'total_collected' => $collected,
                'collection_rate' => round($collectionRate, 2),
                'package_count' => $data->package_count
            ];
        }

        return $weeklyRates;
    }

    /**
     * Calculate collection rates by manifest type
     */
    protected function calculateCollectionRatesByType($dateFrom, $dateTo, $manifestIds, $officeIds): array
    {
        $query = DB::table('manifests as m')
            ->select([
                'm.type as manifest_type',
                DB::raw('SUM(COALESCE(p.freight_price, 0) + COALESCE(p.customs_duty, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)) as total_owed'),
                DB::raw('COUNT(p.id) as package_count'),
                DB::raw('COUNT(DISTINCT m.id) as manifest_count')
            ])
            ->leftJoin('packages as p', 'm.id', '=', 'p.manifest_id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $query->whereIn('m.id', $manifestIds);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        $typeData = $query->groupBy('m.type')->get();

        // Get collected amounts by type
        $collectedByType = $this->getCollectedAmountsByType($dateFrom, $dateTo, $manifestIds, $officeIds);

        $ratesByType = [];
        foreach ($typeData as $data) {
            $collected = $collectedByType[$data->manifest_type] ?? 0;
            $collectionRate = $data->total_owed > 0 ? ($collected / $data->total_owed) * 100 : 0;

            $ratesByType[] = [
                'manifest_type' => $data->manifest_type,
                'total_owed' => (float) $data->total_owed,
                'total_collected' => $collected,
                'collection_rate' => round($collectionRate, 2),
                'package_count' => $data->package_count,
                'manifest_count' => $data->manifest_count,
                'average_per_manifest' => $data->manifest_count > 0 ? 
                    round($data->total_owed / $data->manifest_count, 2) : 0
            ];
        }

        return $ratesByType;
    }

    /**
     * Calculate collection efficiency metrics
     */
    protected function calculateCollectionEfficiency($dateFrom, $dateTo, $manifestIds, $officeIds): array
    {
        // Average days to collect payment
        $avgDaysToCollect = $this->calculateAverageDaysToCollect($dateFrom, $dateTo, $manifestIds, $officeIds);
        
        // Collection velocity (payments per day)
        $collectionVelocity = $this->calculateCollectionVelocity($dateFrom, $dateTo, $manifestIds, $officeIds);
        
        // Payment frequency analysis
        $paymentFrequency = $this->analyzePaymentFrequency($dateFrom, $dateTo, $manifestIds, $officeIds);

        return [
            'average_days_to_collect' => $avgDaysToCollect,
            'collection_velocity' => $collectionVelocity,
            'payment_frequency' => $paymentFrequency,
            'efficiency_score' => $this->calculateEfficiencyScore($avgDaysToCollect, $collectionVelocity)
        ];
    }

    /**
     * Get outstanding balances with aging analysis
     */
    public function getOutstandingBalances(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('outstanding_balances', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchOutstandingBalances($filters);
        });
    }

    /**
     * Fetch outstanding balances from database
     */
    protected function fetchOutstandingBalances(array $filters): array
    {
        $officeIds = $filters['office_ids'] ?? null;
        $customerIds = $filters['customer_ids'] ?? null;

        $query = User::query()
            ->select(['id', 'first_name', 'last_name', 'email', 'account_balance'])
            ->where('account_balance', '<', 0);

        if ($customerIds) {
            $query->whereIn('id', $customerIds);
        }

        if ($officeIds) {
            $query->whereHas('packages', function($q) use ($officeIds) {
                $q->whereIn('office_id', $officeIds);
            });
        }

        $usersWithDebt = $query->orderBy('account_balance', 'asc')->get();

        // Calculate aging buckets
        $agingAnalysis = $this->calculateDetailedAging($usersWithDebt->pluck('id')->toArray());
        
        // Risk assessment
        $riskAssessment = $this->assessCollectionRisk($usersWithDebt);

        return [
            'total_outstanding' => abs($usersWithDebt->sum('account_balance')),
            'customer_count' => $usersWithDebt->count(),
            'aging_analysis' => $agingAnalysis,
            'risk_assessment' => $riskAssessment,
            'top_debtors' => $this->getTopDebtors($usersWithDebt),
            'collection_recommendations' => $this->generateCollectionRecommendations($agingAnalysis, $riskAssessment),
            'generated_at' => now()
        ];
    }

    /**
     * Analyze payment patterns and revenue projections
     */
    public function analyzePaymentTrends(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('payment_trends', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchPaymentTrends($filters);
        });
    }

    /**
     * Fetch payment trends from database
     */
    protected function fetchPaymentTrends(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonths(6);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        // Daily payment patterns
        $dailyPatterns = $this->analyzeDailyPaymentPatterns($dateFrom, $dateTo);
        
        // Seasonal trends
        $seasonalTrends = $this->analyzeSeasonalTrends($dateFrom, $dateTo);
        
        // Customer payment behavior
        $customerBehavior = $this->analyzeCustomerPaymentBehavior($dateFrom, $dateTo);
        
        // Revenue projections
        $projections = $this->generateRevenueProjections($dateFrom, $dateTo);

        return [
            'daily_patterns' => $dailyPatterns,
            'seasonal_trends' => $seasonalTrends,
            'customer_behavior' => $customerBehavior,
            'revenue_projections' => $projections,
            'trend_insights' => $this->generateTrendInsights($dailyPatterns, $seasonalTrends),
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'generated_at' => now()
        ];
    }

    /**
     * Create outstanding balance aging and risk assessment
     */
    public function getRevenueProjections(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('revenue_projections', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchRevenueProjections($filters);
        });
    }

    /**
     * Fetch revenue projections
     */
    protected function fetchRevenueProjections(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonths(6);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        // Historical revenue data for trend analysis
        $historicalRevenue = $this->getHistoricalRevenueData($dateFrom, $dateTo);
        
        // Calculate growth rates
        $growthRates = $this->calculateGrowthRates($historicalRevenue);
        
        // Generate projections for next 3 months
        $projections = $this->projectFutureRevenue($historicalRevenue, $growthRates, 3);
        
        // Confidence intervals
        $confidenceIntervals = $this->calculateConfidenceIntervals($projections);

        return [
            'historical_data' => $historicalRevenue,
            'growth_rates' => $growthRates,
            'projections' => $projections,
            'confidence_intervals' => $confidenceIntervals,
            'projection_accuracy' => $this->assessProjectionAccuracy($historicalRevenue),
            'generated_at' => now()
        ];
    }

    // Helper methods for calculations

    protected function getMonthlyCollectedAmounts($dateFrom, $dateTo, $manifestIds, $officeIds): array
    {
        $query = DB::table('package_distributions as pd')
            ->select([
                DB::raw('YEAR(pd.created_at) as year'),
                DB::raw('MONTH(pd.created_at) as month'),
                DB::raw('SUM(pd.total_amount) as total_collected'),
                'm.type as manifest_type'
            ])
            ->join('package_distribution_items as pdi', 'pd.id', '=', 'pdi.distribution_id')
            ->join('packages as p', 'pdi.package_id', '=', 'p.id')
            ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
            ->whereBetween('pd.created_at', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $query->whereIn('m.id', $manifestIds);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        return $query->groupBy(DB::raw('YEAR(pd.created_at)'), DB::raw('MONTH(pd.created_at)'), 'm.type')
            ->get()
            ->mapWithKeys(function ($item) {
                return ["{$item->year}-{$item->month}-{$item->manifest_type}" => (float) $item->total_collected];
            })
            ->toArray();
    }

    protected function getWeeklyCollectedAmounts($dateFrom, $dateTo, $manifestIds, $officeIds): array
    {
        $query = DB::table('package_distributions as pd')
            ->select([
                DB::raw('YEARWEEK(pd.created_at) as year_week'),
                DB::raw('SUM(pd.total_amount) as total_collected')
            ])
            ->join('package_distribution_items as pdi', 'pd.id', '=', 'pdi.distribution_id')
            ->join('packages as p', 'pdi.package_id', '=', 'p.id')
            ->whereBetween('pd.created_at', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $query->join('manifests as m', 'p.manifest_id', '=', 'm.id')
                  ->whereIn('m.id', $manifestIds);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        return $query->groupBy(DB::raw('YEARWEEK(pd.created_at)'))
            ->get()
            ->pluck('total_collected', 'year_week')
            ->toArray();
    }

    protected function getCollectedAmountsByType($dateFrom, $dateTo, $manifestIds, $officeIds): array
    {
        $query = DB::table('package_distributions as pd')
            ->select([
                'm.type as manifest_type',
                DB::raw('SUM(pd.total_amount) as total_collected')
            ])
            ->join('package_distribution_items as pdi', 'pd.id', '=', 'pdi.distribution_id')
            ->join('packages as p', 'pdi.package_id', '=', 'p.id')
            ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
            ->whereBetween('pd.created_at', [$dateFrom, $dateTo]);

        if ($manifestIds) {
            $query->whereIn('m.id', $manifestIds);
        }

        if ($officeIds) {
            $query->whereIn('p.office_id', $officeIds);
        }

        return $query->groupBy('m.type')
            ->get()
            ->pluck('total_collected', 'manifest_type')
            ->toArray();
    }

    protected function calculateAverageDaysToCollect($dateFrom, $dateTo, $manifestIds, $officeIds): float
    {
        // This would require tracking when charges were created vs when payments were made
        // For now, return a placeholder calculation
        return 15.5; // Average days placeholder
    }

    protected function calculateCollectionVelocity($dateFrom, $dateTo, $manifestIds, $officeIds): float
    {
        $totalDays = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
        $totalPayments = CustomerTransaction::where('type', CustomerTransaction::TYPE_PAYMENT)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        return $totalDays > 0 ? round($totalPayments / $totalDays, 2) : 0;
    }

    protected function analyzePaymentFrequency($dateFrom, $dateTo, $manifestIds, $officeIds): array
    {
        $payments = CustomerTransaction::where('type', CustomerTransaction::TYPE_PAYMENT)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DAYOFWEEK(created_at) as day_of_week, COUNT(*) as payment_count')
            ->groupBy(DB::raw('DAYOFWEEK(created_at)'))
            ->get();

        return $payments->mapWithKeys(function ($payment) {
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return [$dayNames[$payment->day_of_week - 1] => $payment->payment_count];
        })->toArray();
    }

    protected function calculateEfficiencyScore($avgDays, $velocity): float
    {
        // Simple efficiency score calculation
        $daysScore = max(0, 100 - ($avgDays * 2)); // Lower days = higher score
        $velocityScore = min(100, $velocity * 10); // Higher velocity = higher score
        
        return round(($daysScore + $velocityScore) / 2, 2);
    }

    protected function calculateDetailedAging(array $userIds): array
    {
        // Implementation for detailed aging analysis
        return [
            '0-30_days' => ['amount' => 0, 'count' => 0, 'percentage' => 0],
            '31-60_days' => ['amount' => 0, 'count' => 0, 'percentage' => 0],
            '61-90_days' => ['amount' => 0, 'count' => 0, 'percentage' => 0],
            '90+_days' => ['amount' => 0, 'count' => 0, 'percentage' => 0]
        ];
    }

    protected function assessCollectionRisk($usersWithDebt): array
    {
        return [
            'high_risk_customers' => 0,
            'medium_risk_customers' => 0,
            'low_risk_customers' => 0,
            'total_high_risk_amount' => 0
        ];
    }

    protected function getTopDebtors($usersWithDebt): array
    {
        return $usersWithDebt->take(10)->map(function ($user) {
            return [
                'customer_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'outstanding_balance' => abs($user->account_balance)
            ];
        })->toArray();
    }

    protected function generateCollectionRecommendations($agingAnalysis, $riskAssessment): array
    {
        return [
            'immediate_action_required' => [],
            'follow_up_recommended' => [],
            'monitor_closely' => []
        ];
    }

    protected function analyzeTrends($monthlyRates, $weeklyRates): array
    {
        return [
            'trend_direction' => 'stable',
            'growth_rate' => 0,
            'volatility' => 'low'
        ];
    }

    protected function getWeekStartDate($yearWeek): string
    {
        $year = substr($yearWeek, 0, 4);
        $week = substr($yearWeek, 4, 2);
        return Carbon::now()->setISODate($year, $week)->startOfWeek()->format('Y-m-d');
    }

    protected function analyzeDailyPaymentPatterns($dateFrom, $dateTo): array
    {
        return [];
    }

    protected function analyzeSeasonalTrends($dateFrom, $dateTo): array
    {
        return [];
    }

    protected function analyzeCustomerPaymentBehavior($dateFrom, $dateTo): array
    {
        return [];
    }

    protected function generateRevenueProjections($dateFrom, $dateTo): array
    {
        return [];
    }

    protected function generateTrendInsights($dailyPatterns, $seasonalTrends): array
    {
        return [];
    }

    protected function getHistoricalRevenueData($dateFrom, $dateTo): array
    {
        return [];
    }

    protected function calculateGrowthRates($historicalRevenue): array
    {
        return [];
    }

    protected function projectFutureRevenue($historicalRevenue, $growthRates, $months): array
    {
        return [];
    }

    protected function calculateConfidenceIntervals($projections): array
    {
        return [];
    }

    protected function assessProjectionAccuracy($historicalRevenue): array
    {
        return [];
    }

    /**
     * Get collections overview data for charts
     */
    public function getCollectionsOverview(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('collections_overview', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchCollectionsOverview($filters);
        });
    }

    protected function fetchCollectionsOverview(array $filters): array
    {
        $dateFrom = $filters['start_date'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['end_date'] ?? Carbon::now();
        $manifestType = $filters['manifest_type'] ?? null;
        $officeId = $filters['office_id'] ?? null;

        // Get total owed amounts
        $owedQuery = DB::table('packages as p')
            ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestType) {
            $owedQuery->where('m.type', $manifestType);
        }

        if ($officeId) {
            $owedQuery->where('p.office_id', $officeId);
        }

        $totalOwed = $owedQuery->sum(DB::raw('COALESCE(p.freight_price, 0) + COALESCE(p.customs_duty, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)'));

        // Get total collected amounts
        $collectedQuery = DB::table('package_distributions as pd')
            ->join('package_distribution_items as pdi', 'pd.id', '=', 'pdi.distribution_id')
            ->join('packages as p', 'pdi.package_id', '=', 'p.id')
            ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestType) {
            $collectedQuery->where('m.type', $manifestType);
        }

        if ($officeId) {
            $collectedQuery->where('p.office_id', $officeId);
        }

        $totalCollected = $collectedQuery->sum('pd.total_amount');
        $totalOutstanding = $totalOwed - $totalCollected;
        $collectionRate = $totalOwed > 0 ? ($totalCollected / $totalOwed) * 100 : 0;

        return [
            'total_owed' => (float) $totalOwed,
            'total_collected' => (float) $totalCollected,
            'total_outstanding' => (float) $totalOutstanding,
            'collection_rate' => round($collectionRate, 2)
        ];
    }

    /**
     * Get collections trend data for charts
     */
    public function getCollectionsTrend(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('collections_trend', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchCollectionsTrend($filters);
        });
    }

    protected function fetchCollectionsTrend(array $filters): array
    {
        $dateFrom = $filters['start_date'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['end_date'] ?? Carbon::now();
        $manifestType = $filters['manifest_type'] ?? null;
        $officeId = $filters['office_id'] ?? null;

        $periods = [];
        $current = Carbon::parse($dateFrom)->startOfWeek();
        $end = Carbon::parse($dateTo);

        while ($current->lte($end)) {
            $weekEnd = $current->copy()->endOfWeek();
            if ($weekEnd->gt($end)) {
                $weekEnd = $end;
            }

            // Get owed amounts for this period
            $owedQuery = DB::table('packages as p')
                ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
                ->whereBetween('m.shipment_date', [$current, $weekEnd]);

            if ($manifestType) {
                $owedQuery->where('m.type', $manifestType);
            }

            if ($officeId) {
                $owedQuery->where('p.office_id', $officeId);
            }

            $owed = $owedQuery->sum(DB::raw('COALESCE(p.freight_price, 0) + COALESCE(p.customs_duty, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)'));

            // Get collected amounts for this period
            $collectedQuery = DB::table('package_distributions as pd')
                ->join('package_distribution_items as pdi', 'pd.id', '=', 'pdi.distribution_id')
                ->join('packages as p', 'pdi.package_id', '=', 'p.id')
                ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
                ->whereBetween('pd.created_at', [$current, $weekEnd]);

            if ($manifestType) {
                $collectedQuery->where('m.type', $manifestType);
            }

            if ($officeId) {
                $collectedQuery->where('p.office_id', $officeId);
            }

            $collected = $collectedQuery->sum('pd.total_amount');

            $periods[] = [
                'label' => $current->format('M j'),
                'owed' => (float) $owed,
                'collected' => (float) $collected,
                'date' => $current->format('Y-m-d')
            ];

            $current->addWeek();
        }

        $totalOwed = array_sum(array_column($periods, 'owed'));
        $totalCollected = array_sum(array_column($periods, 'collected'));

        return [
            'periods' => $periods,
            'summary' => [
                'total_owed' => $totalOwed,
                'total_collected' => $totalCollected,
                'collection_rate' => $totalOwed > 0 ? round(($totalCollected / $totalOwed) * 100, 2) : 0
            ]
        ];
    }

    /**
     * Get outstanding analysis data for charts
     */
    public function getOutstandingAnalysis(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('outstanding_analysis', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchOutstandingAnalysis($filters);
        });
    }

    protected function fetchOutstandingAnalysis(array $filters): array
    {
        $manifestType = $filters['manifest_type'] ?? null;
        $officeId = $filters['office_id'] ?? null;

        // Get users with negative balances (outstanding amounts)
        $query = User::query()
            ->select(['id', 'first_name', 'last_name', 'account_balance', 'created_at'])
            ->where('account_balance', '<', 0);

        if ($officeId) {
            $query->whereHas('packages', function($q) use ($officeId) {
                $q->where('office_id', $officeId);
            });
        }

        $usersWithDebt = $query->get();

        // Calculate aging buckets
        $now = Carbon::now();
        $agingBuckets = [
            ['label' => '0-30 days', 'amount' => 0, 'count' => 0],
            ['label' => '31-60 days', 'amount' => 0, 'count' => 0],
            ['label' => '61-90 days', 'amount' => 0, 'count' => 0],
            ['label' => '90+ days', 'amount' => 0, 'count' => 0]
        ];

        foreach ($usersWithDebt as $user) {
            $daysSinceCreated = $now->diffInDays($user->created_at);
            $outstandingAmount = abs($user->account_balance);

            if ($daysSinceCreated <= 30) {
                $agingBuckets[0]['amount'] += $outstandingAmount;
                $agingBuckets[0]['count']++;
            } elseif ($daysSinceCreated <= 60) {
                $agingBuckets[1]['amount'] += $outstandingAmount;
                $agingBuckets[1]['count']++;
            } elseif ($daysSinceCreated <= 90) {
                $agingBuckets[2]['amount'] += $outstandingAmount;
                $agingBuckets[2]['count']++;
            } else {
                $agingBuckets[3]['amount'] += $outstandingAmount;
                $agingBuckets[3]['count']++;
            }
        }

        $totalOutstanding = array_sum(array_column($agingBuckets, 'amount'));

        return [
            'aging_buckets' => $agingBuckets,
            'summary' => [
                'total_outstanding' => $totalOutstanding,
                'customer_count' => $usersWithDebt->count(),
                'average_outstanding' => $usersWithDebt->count() > 0 ? 
                    round($totalOutstanding / $usersWithDebt->count(), 2) : 0
            ]
        ];
    }

    /**
     * Get payment patterns data for charts
     */
    public function getPaymentPatterns(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('payment_patterns', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchPaymentPatterns($filters);
        });
    }

    protected function fetchPaymentPatterns(array $filters): array
    {
        $dateFrom = $filters['start_date'] ?? Carbon::now()->subDays(90);
        $dateTo = $filters['end_date'] ?? Carbon::now();

        // Get payment patterns by week
        $patterns = DB::table('customer_transactions')
            ->select([
                DB::raw('YEARWEEK(created_at) as year_week'),
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('AVG(DATEDIFF(created_at, created_at)) as avg_days'), // Placeholder calculation
                DB::raw('SUM(ABS(amount)) as total_amount')
            ])
            ->where('type', CustomerTransaction::TYPE_PAYMENT)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy(DB::raw('YEARWEEK(created_at)'))
            ->orderBy('year_week')
            ->get();

        $formattedPatterns = $patterns->map(function ($pattern) {
            $weekStart = $this->getWeekStartDate($pattern->year_week);
            return [
                'period' => Carbon::parse($weekStart)->format('M j'),
                'payment_count' => $pattern->payment_count,
                'avg_days' => round($pattern->avg_days ?? 15, 1), // Placeholder
                'total_amount' => (float) $pattern->total_amount
            ];
        })->toArray();

        return [
            'patterns' => $formattedPatterns,
            'summary' => [
                'total_payments' => $patterns->sum('payment_count'),
                'average_payment_time' => round($patterns->avg('avg_days') ?? 15, 1),
                'total_payment_volume' => $patterns->sum('total_amount')
            ]
        ];
    }

    /**
     * Get revenue breakdown by service type
     */
    public function getRevenueBreakdown(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('revenue_breakdown', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchRevenueBreakdown($filters);
        });
    }

    protected function fetchRevenueBreakdown(array $filters): array
    {
        $dateFrom = $filters['start_date'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['end_date'] ?? Carbon::now();
        $manifestType = $filters['manifest_type'] ?? null;
        $officeId = $filters['office_id'] ?? null;

        $query = DB::table('packages as p')
            ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
            ->whereBetween('m.shipment_date', [$dateFrom, $dateTo]);

        if ($manifestType) {
            $query->where('m.type', $manifestType);
        }

        if ($officeId) {
            $query->where('p.office_id', $officeId);
        }

        $revenueData = $query->selectRaw('
            SUM(COALESCE(p.freight_price, 0)) as freight_revenue,
            SUM(COALESCE(p.customs_duty, 0)) as customs_revenue,
            SUM(COALESCE(p.storage_fee, 0)) as storage_revenue,
            SUM(COALESCE(p.delivery_fee, 0)) as delivery_revenue
        ')->first();

        $breakdown = [
            ['service' => 'Freight', 'amount' => (float) $revenueData->freight_revenue],
            ['service' => 'Customs', 'amount' => (float) $revenueData->customs_revenue],
            ['service' => 'Storage', 'amount' => (float) $revenueData->storage_revenue],
            ['service' => 'Delivery', 'amount' => (float) $revenueData->delivery_revenue]
        ];

        $totalRevenue = array_sum(array_column($breakdown, 'amount'));

        return [
            'breakdown' => $breakdown,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'highest_service' => collect($breakdown)->sortByDesc('amount')->first()['service'] ?? 'N/A',
                'service_count' => count(array_filter($breakdown, fn($item) => $item['amount'] > 0))
            ]
        ];
    }

    /**
     * Get customer payment patterns for scatter plot
     */
    public function getCustomerPaymentPatterns(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('customer_payment_patterns', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchCustomerPaymentPatterns($filters);
        });
    }

    protected function fetchCustomerPaymentPatterns(array $filters): array
    {
        $dateFrom = $filters['start_date'] ?? Carbon::now()->subDays(90);
        $dateTo = $filters['end_date'] ?? Carbon::now();

        // Get customer payment data
        $customers = DB::table('users as u')
            ->select([
                'u.id as customer_id',
                'u.name as customer_name',
                DB::raw('COUNT(DISTINCT pd.id) as payment_count'),
                DB::raw('SUM(pd.total_amount) as total_revenue'),
                DB::raw('AVG(DATEDIFF(pd.created_at, p.created_at)) as avg_payment_days')
            ])
            ->join('packages as p', 'u.id', '=', 'p.user_id')
            ->join('package_distribution_items as pdi', 'p.id', '=', 'pdi.package_id')
            ->join('package_distributions as pd', 'pdi.distribution_id', '=', 'pd.id')
            ->whereBetween('pd.created_at', [$dateFrom, $dateTo])
            ->groupBy('u.id', 'u.name')
            ->having('payment_count', '>', 0)
            ->orderByDesc('total_revenue')
            ->limit(50)
            ->get();

        $customerData = $customers->map(function($customer) {
            return [
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'payment_count' => $customer->payment_count,
                'total_revenue' => (float) $customer->total_revenue,
                'avg_payment_days' => round($customer->avg_payment_days ?? 15, 1)
            ];
        })->toArray();

        return [
            'customers' => $customerData,
            'summary' => [
                'total_customers' => count($customerData),
                'avg_payment_days' => count($customerData) > 0 ? 
                    round(array_sum(array_column($customerData, 'avg_payment_days')) / count($customerData), 1) : 0,
                'total_revenue' => array_sum(array_column($customerData, 'total_revenue')),
                'top_customer_revenue' => count($customerData) > 0 ? max(array_column($customerData, 'total_revenue')) : 0
            ]
        ];
    }

    /**
     * Get revenue trends over time
     */
    public function getRevenueTrends(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('revenue_trends', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchRevenueTrends($filters);
        });
    }

    protected function fetchRevenueTrends(array $filters): array
    {
        $dateFrom = $filters['start_date'] ?? Carbon::now()->subDays(90);
        $dateTo = $filters['end_date'] ?? Carbon::now();

        $trends = [];
        $current = Carbon::parse($dateFrom)->startOfWeek();
        $end = Carbon::parse($dateTo);

        while ($current->lte($end)) {
            $weekEnd = $current->copy()->endOfWeek();
            if ($weekEnd->gt($end)) {
                $weekEnd = $end;
            }

            // Get revenue for this period
            $revenue = DB::table('packages as p')
                ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
                ->whereBetween('m.shipment_date', [$current, $weekEnd])
                ->sum(DB::raw('COALESCE(p.freight_price, 0) + COALESCE(p.customs_duty, 0) + COALESCE(p.storage_fee, 0) + COALESCE(p.delivery_fee, 0)'));

            // Get collections for this period
            $collections = DB::table('package_distributions as pd')
                ->whereBetween('pd.created_at', [$current, $weekEnd])
                ->sum('pd.total_amount');

            $trends[] = [
                'period' => $current->format('M j'),
                'revenue' => (float) $revenue,
                'collections' => (float) $collections,
                'date' => $current->format('Y-m-d')
            ];

            $current->addWeek();
        }

        $totalRevenue = array_sum(array_column($trends, 'revenue'));
        $totalCollections = array_sum(array_column($trends, 'collections'));

        return [
            'trends' => $trends,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_collections' => $totalCollections,
                'collection_rate' => $totalRevenue > 0 ? round(($totalCollections / $totalRevenue) * 100, 2) : 0,
                'avg_weekly_revenue' => count($trends) > 0 ? round($totalRevenue / count($trends), 2) : 0
            ]
        ];
    }

    /**
     * Get service performance metrics
     */
    public function getServicePerformance(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('service_performance', $filters);
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($filters) {
            return $this->fetchServicePerformance($filters);
        });
    }

    protected function fetchServicePerformance(array $filters): array
    {
        $dateFrom = $filters['start_date'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['end_date'] ?? Carbon::now();

        // Calculate service performance (simplified calculation)
        $services = [
            [
                'name' => 'Freight',
                'revenue' => DB::table('packages as p')
                    ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
                    ->whereBetween('m.shipment_date', [$dateFrom, $dateTo])
                    ->sum('p.freight_price'),
                'margin' => 25.0 // Estimated margin
            ],
            [
                'name' => 'Customs',
                'revenue' => DB::table('packages as p')
                    ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
                    ->whereBetween('m.shipment_date', [$dateFrom, $dateTo])
                    ->sum('p.customs_duty'),
                'margin' => 15.0 // Estimated margin
            ],
            [
                'name' => 'Storage',
                'revenue' => DB::table('packages as p')
                    ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
                    ->whereBetween('m.shipment_date', [$dateFrom, $dateTo])
                    ->sum('p.storage_fee'),
                'margin' => 40.0 // Estimated margin
            ],
            [
                'name' => 'Delivery',
                'revenue' => DB::table('packages as p')
                    ->join('manifests as m', 'p.manifest_id', '=', 'm.id')
                    ->whereBetween('m.shipment_date', [$dateFrom, $dateTo])
                    ->sum('p.delivery_fee'),
                'margin' => 30.0 // Estimated margin
            ]
        ];

        // Convert revenue to float
        foreach ($services as &$service) {
            $service['revenue'] = (float) $service['revenue'];
        }

        $totalRevenue = array_sum(array_column($services, 'revenue'));
        $avgMargin = count($services) > 0 ? 
            array_sum(array_column($services, 'margin')) / count($services) : 0;

        return [
            'services' => $services,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'avg_margin' => round($avgMargin, 1),
                'best_performing_service' => collect($services)->sortByDesc('revenue')->first()['name'] ?? 'N/A',
                'highest_margin_service' => collect($services)->sortByDesc('margin')->first()['name'] ?? 'N/A'
            ]
        ];
    }

    protected function generateCacheKey(string $type, array $filters): string
    {
        $filterHash = md5(serialize($filters));
        return "{$this->cachePrefix}:{$type}:{$filterHash}";
    }
}
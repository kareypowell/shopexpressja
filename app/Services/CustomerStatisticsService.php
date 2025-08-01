<?php

namespace App\Services;

use App\Models\User;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerStatisticsService
{
    /**
     * Cache duration in seconds (1 hour)
     */
    const CACHE_DURATION = 3600;

    /**
     * Get comprehensive customer statistics with caching
     *
     * @param User $customer
     * @return array
     */
    public function getCustomerStatistics(User $customer): array
    {
        $cacheKey = "customer_stats_{$customer->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($customer) {
            return $this->calculateCustomerStatistics($customer);
        });
    }

    /**
     * Get customer financial summary with caching
     *
     * @param User $customer
     * @return array
     */
    public function getFinancialSummary(User $customer): array
    {
        $cacheKey = "customer_financial_{$customer->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($customer) {
            return $this->calculateFinancialSummary($customer);
        });
    }

    /**
     * Get shipping patterns and frequency analysis
     *
     * @param User $customer
     * @return array
     */
    public function getShippingPatterns(User $customer): array
    {
        $cacheKey = "customer_patterns_{$customer->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($customer) {
            return $this->analyzeShippingPatterns($customer);
        });
    }

    /**
     * Get package count and value calculations
     *
     * @param User $customer
     * @return array
     */
    public function getPackageMetrics(User $customer): array
    {
        $cacheKey = "customer_packages_{$customer->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($customer) {
            return $this->calculatePackageMetrics($customer);
        });
    }

    /**
     * Calculate comprehensive customer statistics
     *
     * @param User $customer
     * @return array
     */
    private function calculateCustomerStatistics(User $customer): array
    {
        $packageMetrics = $this->calculatePackageMetrics($customer);
        $financialSummary = $this->calculateFinancialSummary($customer);
        $shippingPatterns = $this->analyzeShippingPatterns($customer);

        return [
            'customer_id' => $customer->id,
            'packages' => $packageMetrics,
            'financial' => $financialSummary,
            'patterns' => $shippingPatterns,
            'generated_at' => Carbon::now(),
        ];
    }

    /**
     * Calculate package metrics and statistics
     *
     * @param User $customer
     * @return array
     */
    private function calculatePackageMetrics(User $customer): array
    {
        $packageStats = $customer->packages()
            ->selectRaw('
                COUNT(*) as total_packages,
                COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered_packages,
                COUNT(CASE WHEN status = "in_transit" THEN 1 END) as in_transit_packages,
                COUNT(CASE WHEN status = "ready_for_pickup" THEN 1 END) as ready_packages,
                COUNT(CASE WHEN status = "delayed" THEN 1 END) as delayed_packages,
                COUNT(CASE WHEN status = "processing" THEN 1 END) as processing_packages,
                COALESCE(AVG(weight), 0) as avg_weight,
                COALESCE(SUM(weight), 0) as total_weight,
                COALESCE(MAX(weight), 0) as max_weight,
                COALESCE(MIN(weight), 0) as min_weight,
                COALESCE(AVG(cubic_feet), 0) as avg_cubic_feet,
                COALESCE(SUM(cubic_feet), 0) as total_cubic_feet
            ')
            ->first();

        // Calculate delivery rate
        $totalPackages = $packageStats->total_packages ?? 0;
        $deliveredPackages = $packageStats->delivered_packages ?? 0;
        $deliveryRate = $totalPackages > 0 ? ($deliveredPackages / $totalPackages) * 100 : 0;

        return [
            'total_count' => $totalPackages,
            'status_breakdown' => [
                'delivered' => $deliveredPackages,
                'in_transit' => $packageStats->in_transit_packages ?? 0,
                'ready_for_pickup' => $packageStats->ready_packages ?? 0,
                'delayed' => $packageStats->delayed_packages ?? 0,
                'processing' => $packageStats->processing_packages ?? 0,
            ],
            'weight_statistics' => [
                'total_weight' => round($packageStats->total_weight ?? 0, 2),
                'average_weight' => round($packageStats->avg_weight ?? 0, 2),
                'max_weight' => round($packageStats->max_weight ?? 0, 2),
                'min_weight' => round($packageStats->min_weight ?? 0, 2),
            ],
            'volume_statistics' => [
                'total_cubic_feet' => round($packageStats->total_cubic_feet ?? 0, 3),
                'average_cubic_feet' => round($packageStats->avg_cubic_feet ?? 0, 3),
            ],
            'delivery_rate' => round($deliveryRate, 2),
        ];
    }

    /**
     * Calculate financial summary and breakdowns
     *
     * @param User $customer
     * @return array
     */
    private function calculateFinancialSummary(User $customer): array
    {
        $financialStats = $customer->packages()
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

        $totalSpent = ($financialStats->total_freight ?? 0) + 
                     ($financialStats->total_customs ?? 0) + 
                     ($financialStats->total_storage ?? 0) + 
                     ($financialStats->total_delivery ?? 0);

        $totalPackages = $financialStats->total_packages ?? 0;
        $averagePerPackage = $totalPackages > 0 ? $totalSpent / $totalPackages : 0;

        // Calculate cost distribution percentages
        $freightPercentage = $totalSpent > 0 ? (($financialStats->total_freight ?? 0) / $totalSpent) * 100 : 0;
        $customsPercentage = $totalSpent > 0 ? (($financialStats->total_customs ?? 0) / $totalSpent) * 100 : 0;
        $storagePercentage = $totalSpent > 0 ? (($financialStats->total_storage ?? 0) / $totalSpent) * 100 : 0;
        $deliveryPercentage = $totalSpent > 0 ? (($financialStats->total_delivery ?? 0) / $totalSpent) * 100 : 0;

        return [
            'total_spent' => round($totalSpent, 2),
            'average_per_package' => round($averagePerPackage, 2),
            'cost_breakdown' => [
                'freight' => round($financialStats->total_freight ?? 0, 2),
                'customs' => round($financialStats->total_customs ?? 0, 2),
                'storage' => round($financialStats->total_storage ?? 0, 2),
                'delivery' => round($financialStats->total_delivery ?? 0, 2),
            ],
            'cost_percentages' => [
                'freight' => round($freightPercentage, 1),
                'customs' => round($customsPercentage, 1),
                'storage' => round($storagePercentage, 1),
                'delivery' => round($deliveryPercentage, 1),
            ],
            'average_costs' => [
                'freight' => round($financialStats->avg_freight ?? 0, 2),
                'customs' => round($financialStats->avg_customs ?? 0, 2),
                'storage' => round($financialStats->avg_storage ?? 0, 2),
                'delivery' => round($financialStats->avg_delivery ?? 0, 2),
            ],
            'cost_range' => [
                'highest_package' => round($financialStats->highest_package_cost ?? 0, 2),
                'lowest_package' => round($financialStats->lowest_package_cost ?? 0, 2),
            ],
        ];
    }

    /**
     * Analyze shipping patterns and frequency
     *
     * @param User $customer
     * @return array
     */
    private function analyzeShippingPatterns(User $customer): array
    {
        // Get first and last package dates
        $firstPackage = $customer->packages()->oldest('created_at')->first();
        $lastPackage = $customer->packages()->latest('created_at')->first();
        
        if (!$firstPackage) {
            return [
                'shipping_frequency' => 0,
                'months_active' => 0,
                'first_shipment' => null,
                'last_shipment' => null,
                'monthly_breakdown' => [],
                'seasonal_patterns' => [],
                'average_days_between_shipments' => 0,
            ];
        }

        $firstDate = Carbon::parse($firstPackage->created_at);
        $lastDate = Carbon::parse($lastPackage->created_at);
        $monthsActive = max(1, $firstDate->diffInMonths($lastDate) + 1);
        $totalPackages = $customer->packages()->count();
        
        // Calculate shipping frequency (packages per month)
        $shippingFrequency = $totalPackages / $monthsActive;

        // Get monthly breakdown for the last 12 months
        $monthlyBreakdown = $this->getMonthlyBreakdown($customer);

        // Analyze seasonal patterns
        $seasonalPatterns = $this->analyzeSeasonalPatterns($customer);

        // Calculate average days between shipments
        $averageDaysBetween = $this->calculateAverageDaysBetweenShipments($customer);

        return [
            'shipping_frequency' => round($shippingFrequency, 2),
            'months_active' => $monthsActive,
            'first_shipment' => $firstDate->toDateString(),
            'last_shipment' => $lastDate->toDateString(),
            'monthly_breakdown' => $monthlyBreakdown,
            'seasonal_patterns' => $seasonalPatterns,
            'average_days_between_shipments' => $averageDaysBetween,
        ];
    }

    /**
     * Get monthly package breakdown for the last 12 months
     *
     * @param User $customer
     * @return array
     */
    private function getMonthlyBreakdown(User $customer): array
    {
        // Use database-agnostic date functions
        $monthlyData = $customer->packages()
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->selectRaw('
                strftime("%Y", created_at) as year,
                strftime("%m", created_at) as month,
                COUNT(*) as package_count,
                COALESCE(SUM(freight_price + customs_duty + storage_fee + delivery_fee), 0) as total_spent
            ')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $breakdown = [];
        foreach ($monthlyData as $data) {
            $monthName = Carbon::createFromDate($data->year, $data->month, 1)->format('M Y');
            $breakdown[] = [
                'month' => $monthName,
                'year' => (int) $data->year,
                'month_number' => (int) $data->month,
                'package_count' => $data->package_count,
                'total_spent' => round($data->total_spent, 2),
            ];
        }

        return $breakdown;
    }

    /**
     * Analyze seasonal shipping patterns
     *
     * @param User $customer
     * @return array
     */
    private function analyzeSeasonalPatterns(User $customer): array
    {
        $seasonalData = $customer->packages()
            ->selectRaw('
                CASE 
                    WHEN CAST(strftime("%m", created_at) AS INTEGER) IN (12, 1, 2) THEN "Winter"
                    WHEN CAST(strftime("%m", created_at) AS INTEGER) IN (3, 4, 5) THEN "Spring"
                    WHEN CAST(strftime("%m", created_at) AS INTEGER) IN (6, 7, 8) THEN "Summer"
                    WHEN CAST(strftime("%m", created_at) AS INTEGER) IN (9, 10, 11) THEN "Fall"
                END as season,
                COUNT(*) as package_count,
                COALESCE(AVG(freight_price + customs_duty + storage_fee + delivery_fee), 0) as avg_cost
            ')
            ->groupBy('season')
            ->get();

        $patterns = [];
        foreach ($seasonalData as $data) {
            if ($data->season) {
                $patterns[$data->season] = [
                    'package_count' => $data->package_count,
                    'average_cost' => round($data->avg_cost, 2),
                ];
            }
        }

        return $patterns;
    }

    /**
     * Calculate average days between shipments
     *
     * @param User $customer
     * @return int
     */
    private function calculateAverageDaysBetweenShipments(User $customer): int
    {
        $packageDates = $customer->packages()
            ->orderBy('created_at')
            ->pluck('created_at')
            ->map(function ($date) {
                return Carbon::parse($date);
            });

        if ($packageDates->count() < 2) {
            return 0;
        }

        $totalDays = 0;
        $intervals = 0;

        for ($i = 1; $i < $packageDates->count(); $i++) {
            $daysDiff = $packageDates[$i]->diffInDays($packageDates[$i - 1]);
            $totalDays += $daysDiff;
            $intervals++;
        }

        return $intervals > 0 ? round($totalDays / $intervals) : 0;
    }

    /**
     * Clear cache for a specific customer
     *
     * @param User $customer
     * @return void
     */
    public function clearCustomerCache(User $customer): void
    {
        $cacheKeys = [
            "customer_stats_{$customer->id}",
            "customer_financial_{$customer->id}",
            "customer_patterns_{$customer->id}",
            "customer_packages_{$customer->id}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear all customer statistics cache
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        // This would require a more sophisticated cache tagging system
        // For now, we'll implement a simple approach
        Cache::flush();
    }

    /**
     * Get cache status for a customer
     *
     * @param User $customer
     * @return array
     */
    public function getCacheStatus(User $customer): array
    {
        $cacheKeys = [
            'stats' => "customer_stats_{$customer->id}",
            'financial' => "customer_financial_{$customer->id}",
            'patterns' => "customer_patterns_{$customer->id}",
            'packages' => "customer_packages_{$customer->id}",
        ];

        $status = [];
        foreach ($cacheKeys as $type => $key) {
            $status[$type] = Cache::has($key);
        }

        return $status;
    }
}
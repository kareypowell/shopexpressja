<?php

namespace Database\Factories;

use App\Models\SavedReportFilter;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedReportFilterFactory extends Factory
{
    protected $model = SavedReportFilter::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true) . ' Filter',
            'report_type' => $this->faker->randomElement([
                ReportTemplate::TYPE_SALES,
                ReportTemplate::TYPE_MANIFEST,
                ReportTemplate::TYPE_CUSTOMER,
                ReportTemplate::TYPE_FINANCIAL
            ]),
            'filter_config' => [
                'date_range' => $this->faker->randomElement(['last_7_days', 'last_30_days', 'last_90_days', 'custom']),
                'start_date' => $this->faker->optional()->date(),
                'end_date' => $this->faker->optional()->date(),
                'manifest_types' => $this->faker->randomElements(['air', 'sea'], $this->faker->numberBetween(0, 2)),
                'office_ids' => $this->faker->randomElements([1, 2, 3, 4], $this->faker->numberBetween(0, 2)),
                'customer_ids' => [],
                'status_filters' => $this->faker->randomElements(['pending', 'processing', 'shipped', 'delivered'], $this->faker->numberBetween(0, 3))
            ],
            'is_shared' => $this->faker->boolean(30),
            'shared_with_roles' => $this->faker->boolean(30) ? [1, 2] : null
        ];
    }

    /**
     * Indicate that the filter is shared.
     */
    public function shared()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_shared' => true,
                'shared_with_roles' => [1, 2, 3] // Share with multiple roles
            ];
        });
    }

    /**
     * Indicate that the filter is private.
     */
    public function private()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_shared' => false,
                'shared_with_roles' => null
            ];
        });
    }

    /**
     * Create a sales report filter.
     */
    public function salesFilter()
    {
        return $this->state(function (array $attributes) {
            return [
                'report_type' => ReportTemplate::TYPE_SALES,
                'name' => 'Monthly Sales Filter',
                'filter_config' => [
                    'date_range' => 'last_30_days',
                    'manifest_types' => ['air', 'sea'],
                    'include_outstanding' => true,
                    'include_paid' => true
                ]
            ];
        });
    }

    /**
     * Create a manifest report filter.
     */
    public function manifestFilter()
    {
        return $this->state(function (array $attributes) {
            return [
                'report_type' => ReportTemplate::TYPE_MANIFEST,
                'name' => 'Air Manifest Filter',
                'filter_config' => [
                    'date_range' => 'last_90_days',
                    'manifest_types' => ['air'],
                    'status_filters' => ['shipped', 'delivered']
                ]
            ];
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportTemplateFactory extends Factory
{
    protected $model = ReportTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true) . ' Report Template',
            'type' => $this->faker->randomElement([
                ReportTemplate::TYPE_SALES,
                ReportTemplate::TYPE_MANIFEST,
                ReportTemplate::TYPE_CUSTOMER,
                ReportTemplate::TYPE_FINANCIAL
            ]),
            'description' => $this->faker->sentence(),
            'template_config' => [
                'chart_types' => $this->faker->randomElements(['line', 'bar', 'pie', 'doughnut'], 2),
                'layout' => $this->faker->randomElement(['standard', 'compact', 'detailed']),
                'include_charts' => $this->faker->boolean(80),
                'include_tables' => $this->faker->boolean(90),
                'color_scheme' => $this->faker->randomElement(['blue', 'green', 'red', 'purple'])
            ],
            'default_filters' => [
                'date_range' => $this->faker->randomElement(['last_7_days', 'last_30_days', 'last_90_days']),
                'include_all_offices' => $this->faker->boolean(70),
                'include_all_manifest_types' => $this->faker->boolean(80)
            ],
            'created_by' => User::factory(),
            'is_active' => $this->faker->boolean(85)
        ];
    }

    /**
     * Indicate that the template is active.
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Create a sales report template.
     */
    public function salesReport()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => ReportTemplate::TYPE_SALES,
                'name' => 'Sales & Collections Report',
                'template_config' => [
                    'chart_types' => ['line', 'bar'],
                    'layout' => 'detailed',
                    'include_charts' => true,
                    'include_tables' => true,
                    'show_trends' => true
                ]
            ];
        });
    }

    /**
     * Create a manifest report template.
     */
    public function manifestReport()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => ReportTemplate::TYPE_MANIFEST,
                'name' => 'Manifest Performance Report',
                'template_config' => [
                    'chart_types' => ['bar', 'line'],
                    'layout' => 'standard',
                    'include_charts' => true,
                    'include_tables' => true,
                    'show_efficiency_metrics' => true
                ]
            ];
        });
    }
}

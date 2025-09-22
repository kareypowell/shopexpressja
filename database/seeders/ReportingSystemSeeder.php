<?php

namespace Database\Seeders;

use App\Models\ReportTemplate;
use App\Models\SavedReportFilter;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportingSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get or create an admin user for the templates
        $adminUser = User::where('email', 'admin@shipsharkltd.com')->first();
        if (!$adminUser) {
            // If no admin user exists, use the first user or create a basic one
            $adminUser = User::first();
            if (!$adminUser) {
                $adminUser = User::create([
                    'first_name' => 'System',
                    'last_name' => 'Admin',
                    'email' => 'system@shipsharkltd.com',
                    'password' => bcrypt('password'),
                    'role_id' => 1
                ]);
            }
        }

        // Create default report templates
        $templates = [
            [
                'name' => 'Sales & Collections Summary',
                'type' => ReportTemplate::TYPE_SALES,
                'description' => 'Comprehensive sales and collections report with revenue trends',
                'template_config' => [
                    'chart_types' => ['line', 'bar'],
                    'layout' => 'detailed',
                    'include_charts' => true,
                    'include_tables' => true,
                    'show_trends' => true,
                    'color_scheme' => 'blue'
                ],
                'default_filters' => [
                    'date_range' => 'last_30_days',
                    'include_all_offices' => true,
                    'include_all_manifest_types' => true,
                    'include_outstanding' => true
                ]
            ],
            [
                'name' => 'Manifest Performance Analysis',
                'type' => ReportTemplate::TYPE_MANIFEST,
                'description' => 'Air and sea manifest performance comparison with efficiency metrics',
                'template_config' => [
                    'chart_types' => ['bar', 'line'],
                    'layout' => 'standard',
                    'include_charts' => true,
                    'include_tables' => true,
                    'show_efficiency_metrics' => true,
                    'color_scheme' => 'green'
                ],
                'default_filters' => [
                    'date_range' => 'last_90_days',
                    'include_all_offices' => true,
                    'include_all_manifest_types' => true
                ]
            ],
            [
                'name' => 'Customer Analytics Dashboard',
                'type' => ReportTemplate::TYPE_CUSTOMER,
                'description' => 'Customer behavior analysis with registration trends and activity metrics',
                'template_config' => [
                    'chart_types' => ['pie', 'line'],
                    'layout' => 'compact',
                    'include_charts' => true,
                    'include_tables' => true,
                    'show_demographics' => true,
                    'color_scheme' => 'purple'
                ],
                'default_filters' => [
                    'date_range' => 'last_90_days',
                    'include_all_offices' => true,
                    'customer_status' => 'active'
                ]
            ],
            [
                'name' => 'Financial Summary Report',
                'type' => ReportTemplate::TYPE_FINANCIAL,
                'description' => 'Complete financial overview with revenue, expenses, and profit analysis',
                'template_config' => [
                    'chart_types' => ['bar', 'doughnut'],
                    'layout' => 'detailed',
                    'include_charts' => true,
                    'include_tables' => true,
                    'show_profit_margins' => true,
                    'color_scheme' => 'red'
                ],
                'default_filters' => [
                    'date_range' => 'last_30_days',
                    'include_all_offices' => true,
                    'include_all_transaction_types' => true
                ]
            ]
        ];

        foreach ($templates as $templateData) {
            ReportTemplate::create(array_merge($templateData, [
                'created_by' => $adminUser->id,
                'is_active' => true
            ]));
        }

        // Create some sample saved filters
        $filters = [
            [
                'user_id' => $adminUser->id,
                'name' => 'Last 7 Days Sales',
                'report_type' => ReportTemplate::TYPE_SALES,
                'filter_config' => [
                    'date_range' => 'last_7_days',
                    'manifest_types' => ['air', 'sea'],
                    'include_outstanding' => true,
                    'include_paid' => true
                ],
                'is_shared' => true,
                'shared_with_roles' => [1, 2] // Admin and Manager roles
            ],
            [
                'user_id' => $adminUser->id,
                'name' => 'Air Manifest Performance',
                'report_type' => ReportTemplate::TYPE_MANIFEST,
                'filter_config' => [
                    'date_range' => 'last_30_days',
                    'manifest_types' => ['air'],
                    'status_filters' => ['shipped', 'delivered']
                ],
                'is_shared' => false
            ],
            [
                'user_id' => $adminUser->id,
                'name' => 'New Customer Registrations',
                'report_type' => ReportTemplate::TYPE_CUSTOMER,
                'filter_config' => [
                    'date_range' => 'last_90_days',
                    'registration_period' => 'new_customers_only',
                    'include_demographics' => true
                ],
                'is_shared' => true,
                'shared_with_roles' => [1] // Admin only
            ]
        ];

        foreach ($filters as $filterData) {
            SavedReportFilter::create($filterData);
        }

        $this->command->info('Reporting system seeded successfully!');
        $this->command->info('Created ' . count($templates) . ' report templates');
        $this->command->info('Created ' . count($filters) . ' saved filters');
    }
}

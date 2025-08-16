<?php

namespace Database\Factories;

use App\Models\ConsolidationHistory;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsolidationHistoryFactory extends Factory
{
    protected $model = ConsolidationHistory::class;

    public function definition()
    {
        $actions = ['consolidated', 'unconsolidated', 'status_changed'];
        $action = $this->faker->randomElement($actions);

        return [
            'consolidated_package_id' => ConsolidatedPackage::factory(),
            'action' => $action,
            'performed_by' => User::factory(),
            'details' => $this->generateDetailsForAction($action),
            'performed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    protected function generateDetailsForAction(string $action): array
    {
        switch ($action) {
            case 'consolidated':
                return [
                    'package_count' => $this->faker->numberBetween(2, 5),
                    'total_weight' => $this->faker->randomFloat(2, 5, 50),
                    'total_cost' => $this->faker->randomFloat(2, 50, 500),
                    'package_ids' => $this->faker->randomElements(range(1, 100), $this->faker->numberBetween(2, 5)),
                ];

            case 'unconsolidated':
                return [
                    'package_count' => $this->faker->numberBetween(2, 5),
                    'reason' => $this->faker->randomElement([
                        'Customer request',
                        'Processing error',
                        'Different destinations',
                        'Manual separation',
                    ]),
                    'package_ids' => $this->faker->randomElements(range(1, 100), $this->faker->numberBetween(2, 5)),
                ];

            case 'status_changed':
                $statuses = ['pending', 'processing', 'shipped', 'customs', 'ready', 'delivered'];
                $oldStatus = $this->faker->randomElement($statuses);
                $newStatus = $this->faker->randomElement(array_diff($statuses, [$oldStatus]));
                
                return [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'package_count' => $this->faker->numberBetween(1, 5),
                    'reason' => $this->faker->randomElement([
                        'Status updated by admin',
                        'Automatic status change',
                        'Manifest processing',
                        'Customer pickup',
                    ]),
                ];

            default:
                return [];
        }
    }

    public function consolidated()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'consolidated',
                'details' => $this->generateDetailsForAction('consolidated'),
            ];
        });
    }

    public function unconsolidated()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'unconsolidated',
                'details' => $this->generateDetailsForAction('unconsolidated'),
            ];
        });
    }

    public function statusChanged()
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'status_changed',
                'details' => $this->generateDetailsForAction('status_changed'),
            ];
        });
    }
}
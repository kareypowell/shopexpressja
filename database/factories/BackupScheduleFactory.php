<?php

namespace Database\Factories;

use App\Models\BackupSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BackupScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BackupSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $frequencies = ['daily', 'weekly', 'monthly'];
        $types = ['database', 'files', 'full'];
        
        return [
            'name' => $this->faker->words(3, true) . ' Backup',
            'type' => $this->faker->randomElement($types),
            'frequency' => $this->faker->randomElement($frequencies),
            'time' => $this->faker->time('H:i:s'),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'retention_days' => $this->faker->numberBetween(7, 90),
            'last_run_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'next_run_at' => $this->faker->dateTimeBetween('now', '+7 days'),
        ];
    }

    /**
     * Indicate that the schedule is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
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
     * Indicate that the schedule is inactive.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
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
     * Indicate that the schedule is due to run.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function due()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
                'next_run_at' => Carbon::now()->subMinutes(5),
            ];
        });
    }

    /**
     * Indicate that the schedule is not due yet.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function notDue()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
                'next_run_at' => Carbon::now()->addHours(2),
            ];
        });
    }

    /**
     * Create a daily backup schedule.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function daily()
    {
        return $this->state(function (array $attributes) {
            return [
                'frequency' => 'daily',
                'time' => '02:00:00',
            ];
        });
    }

    /**
     * Create a weekly backup schedule.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function weekly()
    {
        return $this->state(function (array $attributes) {
            return [
                'frequency' => 'weekly',
                'time' => '03:00:00',
            ];
        });
    }

    /**
     * Create a monthly backup schedule.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function monthly()
    {
        return $this->state(function (array $attributes) {
            return [
                'frequency' => 'monthly',
                'time' => '04:00:00',
            ];
        });
    }

    /**
     * Create a database-only backup schedule.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function databaseOnly()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'database',
                'name' => 'Database Backup',
            ];
        });
    }

    /**
     * Create a files-only backup schedule.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function filesOnly()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'files',
                'name' => 'Files Backup',
            ];
        });
    }

    /**
     * Create a full backup schedule.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function fullBackup()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'full',
                'name' => 'Full System Backup',
            ];
        });
    }
}
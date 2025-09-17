<?php

namespace Database\Factories;

use App\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Backup::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $types = ['database', 'files', 'full'];
        $statuses = ['pending', 'completed', 'failed'];
        
        return [
            'name' => $this->faker->words(3, true) . '_backup',
            'type' => $this->faker->randomElement($types),
            'file_path' => $this->faker->filePath(),
            'file_size' => $this->faker->numberBetween(1024, 104857600), // 1KB to 100MB
            'status' => $this->faker->randomElement($statuses),
            'created_by' => null, // Will be set to user ID if needed
            'metadata' => [
                'compression_level' => $this->faker->numberBetween(1, 9),
                'backup_duration' => $this->faker->numberBetween(10, 300),
                'source_size' => $this->faker->numberBetween(1048576, 1073741824), // 1MB to 1GB
            ],
            'checksum' => $this->faker->sha256,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'completed_at' => function (array $attributes) {
                return $attributes['status'] === 'completed' 
                    ? $this->faker->dateTimeBetween($attributes['created_at'], 'now')
                    : null;
            },
        ];
    }

    /**
     * Indicate that the backup is completed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'completed_at' => $this->faker->dateTimeBetween($attributes['created_at'] ?? '-1 hour', 'now'),
            ];
        });
    }

    /**
     * Indicate that the backup failed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'completed_at' => null,
                'file_size' => null,
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'error_message' => $this->faker->sentence(),
                    'error_code' => $this->faker->numberBetween(1, 999),
                ]),
            ];
        });
    }

    /**
     * Indicate that the backup is pending.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'completed_at' => null,
                'file_size' => null,
                'checksum' => null,
            ];
        });
    }

    /**
     * Indicate that the backup is a database backup.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function database()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'database',
                'name' => 'database_backup_' . $this->faker->dateTime()->format('Y-m-d_H-i-s'),
                'file_path' => storage_path('app/backups/database/' . $this->faker->uuid . '.sql'),
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'tables_count' => $this->faker->numberBetween(10, 100),
                    'rows_count' => $this->faker->numberBetween(1000, 1000000),
                ]),
            ];
        });
    }

    /**
     * Indicate that the backup is a files backup.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function files()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'files',
                'name' => 'files_backup_' . $this->faker->dateTime()->format('Y-m-d_H-i-s'),
                'file_path' => storage_path('app/backups/files/' . $this->faker->uuid . '.zip'),
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'files_count' => $this->faker->numberBetween(100, 10000),
                    'directories_count' => $this->faker->numberBetween(10, 100),
                ]),
            ];
        });
    }

    /**
     * Indicate that the backup is a full backup.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function full()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'full',
                'name' => 'full_backup_' . $this->faker->dateTime()->format('Y-m-d_H-i-s'),
                'file_path' => json_encode([
                    'database' => storage_path('app/backups/database/' . $this->faker->uuid . '.sql'),
                    'files' => storage_path('app/backups/files/' . $this->faker->uuid . '.zip'),
                ]),
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'database_size' => $this->faker->numberBetween(1048576, 104857600),
                    'files_size' => $this->faker->numberBetween(1048576, 1073741824),
                ]),
            ];
        });
    }

    /**
     * Indicate that the backup is old (created more than retention period ago).
     *
     * @param int $daysOld Number of days old
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function old($daysOld = 35)
    {
        return $this->state(function (array $attributes) use ($daysOld) {
            $createdAt = now()->subDays($daysOld);
            return [
                'created_at' => $createdAt,
                'completed_at' => $attributes['status'] === 'completed' 
                    ? $createdAt->addMinutes($this->faker->numberBetween(5, 60))
                    : null,
            ];
        });
    }

    /**
     * Indicate that the backup has a specific size.
     *
     * @param int $sizeInBytes Size in bytes
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withSize($sizeInBytes)
    {
        return $this->state(function (array $attributes) use ($sizeInBytes) {
            return [
                'file_size' => $sizeInBytes,
            ];
        });
    }

    /**
     * Indicate that the backup was created by a specific user.
     *
     * @param int $userId User ID
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function createdBy($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'created_by' => $userId,
            ];
        });
    }
}
<?php

namespace Database\Factories;

use App\Models\ReportExportJob;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportExportJobFactory extends Factory
{
    protected $model = ReportExportJob::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = $this->faker->randomElement([
            ReportExportJob::STATUS_PENDING,
            ReportExportJob::STATUS_PROCESSING,
            ReportExportJob::STATUS_COMPLETED,
            ReportExportJob::STATUS_FAILED
        ]);

        $startedAt = null;
        $completedAt = null;
        $filePath = null;
        $errorMessage = null;

        if (in_array($status, [ReportExportJob::STATUS_PROCESSING, ReportExportJob::STATUS_COMPLETED, ReportExportJob::STATUS_FAILED])) {
            $startedAt = $this->faker->dateTimeBetween('-1 hour', 'now');
        }

        if (in_array($status, [ReportExportJob::STATUS_COMPLETED, ReportExportJob::STATUS_FAILED])) {
            $completedAt = $this->faker->dateTimeBetween($startedAt ?? '-30 minutes', 'now');
        }

        if ($status === ReportExportJob::STATUS_COMPLETED) {
            $format = $this->faker->randomElement([ReportExportJob::FORMAT_PDF, ReportExportJob::FORMAT_CSV]);
            $filePath = 'exports/reports/' . $this->faker->uuid . '.' . $format;
        }

        if ($status === ReportExportJob::STATUS_FAILED) {
            $errorMessage = $this->faker->randomElement([
                'Database connection timeout',
                'Insufficient memory to process large dataset',
                'Invalid filter parameters provided',
                'Export file generation failed'
            ]);
        }

        return [
            'user_id' => User::factory(),
            'report_type' => $this->faker->randomElement([
                ReportTemplate::TYPE_SALES,
                ReportTemplate::TYPE_MANIFEST,
                ReportTemplate::TYPE_CUSTOMER,
                ReportTemplate::TYPE_FINANCIAL
            ]),
            'export_format' => $this->faker->randomElement([
                ReportExportJob::FORMAT_PDF,
                ReportExportJob::FORMAT_CSV
            ]),
            'filters' => [
                'date_range' => $this->faker->randomElement(['last_7_days', 'last_30_days', 'last_90_days']),
                'manifest_types' => $this->faker->randomElements(['air', 'sea'], $this->faker->numberBetween(1, 2)),
                'office_ids' => $this->faker->randomElements([1, 2, 3], $this->faker->numberBetween(0, 2))
            ],
            'status' => $status,
            'file_path' => $filePath,
            'error_message' => $errorMessage,
            'started_at' => $startedAt,
            'completed_at' => $completedAt
        ];
    }

    /**
     * Indicate that the job is pending.
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ReportExportJob::STATUS_PENDING,
                'started_at' => null,
                'completed_at' => null,
                'file_path' => null,
                'error_message' => null
            ];
        });
    }

    /**
     * Indicate that the job is processing.
     */
    public function processing()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ReportExportJob::STATUS_PROCESSING,
                'started_at' => now()->subMinutes(5),
                'completed_at' => null,
                'file_path' => null,
                'error_message' => null
            ];
        });
    }

    /**
     * Indicate that the job is completed.
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            $format = $attributes['export_format'] ?? ReportExportJob::FORMAT_PDF;
            return [
                'status' => ReportExportJob::STATUS_COMPLETED,
                'started_at' => now()->subMinutes(10),
                'completed_at' => now()->subMinutes(2),
                'file_path' => 'exports/reports/' . $this->faker->uuid . '.' . $format,
                'error_message' => null
            ];
        });
    }

    /**
     * Indicate that the job has failed.
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ReportExportJob::STATUS_FAILED,
                'started_at' => now()->subMinutes(15),
                'completed_at' => now()->subMinutes(10),
                'file_path' => null,
                'error_message' => 'Export processing failed due to timeout'
            ];
        });
    }

    /**
     * Create a PDF export job.
     */
    public function pdf()
    {
        return $this->state(function (array $attributes) {
            return [
                'export_format' => ReportExportJob::FORMAT_PDF
            ];
        });
    }

    /**
     * Create a CSV export job.
     */
    public function csv()
    {
        return $this->state(function (array $attributes) {
            return [
                'export_format' => ReportExportJob::FORMAT_CSV
            ];
        });
    }
}

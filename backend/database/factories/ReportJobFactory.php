<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'report_type' => $this->faker->randomElement(['detail', 'summary', 'top_n', 'per_student']),
            'format' => $this->faker->randomElement(['pdf', 'excel']),
            'filters' => [
                'event_type' => 'page_view',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ],
            'status' => $this->faker->randomElement(['queued', 'running', 'completed', 'failed']),
            'progress_percent' => $this->faker->numberBetween(0, 100),
            'current_section' => $this->faker->randomElement(['Initializing', 'Querying data', 'Generating export', 'Finalizing']),
            'total_rows' => $this->faker->numberBetween(100, 10000),
            'processed_rows' => $this->faker->numberBetween(0, 10000),
            'file_path' => 'reports/test-' . $this->faker->uuid() . '.pdf',
            'file_size' => $this->faker->numberBetween(100000, 10000000),
            'error_message' => null,
            'checkpoint_data' => null,
            'retry_count' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'progress_percent' => 100,
            'current_section' => 'Completed',
            'processed_rows' => $attributes['total_rows'] ?? 1000,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Test error message',
        ]);
    }

    public function running(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'running',
            'progress_percent' => $this->faker->numberBetween(10, 90),
        ]);
    }
}

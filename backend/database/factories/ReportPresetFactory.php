<?php

namespace Database\Factories;

use App\Models\ReportPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportPreset>
 */
class ReportPresetFactory extends Factory
{
    protected $model = ReportPreset::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $reportTypes = ['detail', 'summary', 'top_n', 'per_student'];

        return [
            'user_id' => 1,
            'name' => $this->faker->words(3, true) . ' Report',
            'report_type' => $this->faker->randomElement($reportTypes),
            'filters' => [
                'date_from' => $this->faker->date(),
                'date_to' => $this->faker->date(),
                'term_ids' => [$this->faker->numberBetween(1, 4)],
            ],
        ];
    }
}

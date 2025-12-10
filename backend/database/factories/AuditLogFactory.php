<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'reports.preview',
                'reports.export',
                'reports.index',
                'audit.index',
            ]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'request_data' => [
                'report_type' => 'detail',
                'format' => 'pdf',
            ],
            'response_status' => $this->faker->randomElement([200, 201, 400, 404, 500]),
            'duration_ms' => $this->faker->numberBetween(50, 2000),
        ];
    }
}

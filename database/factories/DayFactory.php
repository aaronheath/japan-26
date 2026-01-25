<?php

namespace Database\Factories;

use App\Models\ProjectVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Day>
 */
class DayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_version_id' => ProjectVersion::factory(),
            'date' => fake()->date(),
            'number' => fake()->numberBetween(1, 14),
        ];
    }
}

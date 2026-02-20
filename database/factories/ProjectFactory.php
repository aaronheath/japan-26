<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fn () => fake()->unique()->words(3, true),
            'start_date' => fn () => fake()->dateTimeBetween('now', '+1 month'),
            'end_date' => fn () => fake()->dateTimeBetween('+1 month', '+2 months'),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LlmRegenerationBatch>
 */
class LlmRegenerationBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => fn () => Project::factory(),
            'batch_id' => fn () => fake()->uuid(),
            'scope' => fn () => fake()->randomElement(['single', 'day', 'column', 'project']),
            'generator_type' => fn () => fake()->randomElement(['travel', 'sightseeing', 'wrestling', 'eating', null]),
            'total_jobs' => fn () => fake()->numberBetween(1, 20),
            'completed_jobs' => fn () => 0,
            'failed_jobs' => fn () => 0,
            'status' => fn () => 'pending',
            'started_at' => fn () => null,
            'completed_at' => fn () => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'completed_jobs' => $attributes['total_jobs'],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'failed_jobs' => fn () => fake()->numberBetween(1, $attributes['total_jobs']),
        ]);
    }
}

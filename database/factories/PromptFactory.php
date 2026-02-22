<?php

namespace Database\Factories;

use App\Enums\PromptType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prompt>
 */
class PromptFactory extends Factory
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
            'slug' => fn () => fake()->unique()->slug(3),
            'description' => fn () => fake()->sentence(),
            'type' => fn () => PromptType::System,
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => [
            'type' => PromptType::System,
        ]);
    }

    public function task(): static
    {
        return $this->state(fn () => [
            'type' => PromptType::Task,
        ]);
    }
}

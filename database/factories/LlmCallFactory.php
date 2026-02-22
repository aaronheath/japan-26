<?php

namespace Database\Factories;

use App\Enums\LlmModels;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LlmCall>
 */
class LlmCallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'llm_provider_name' => fn () => fake()->randomElement(LlmModels::cases()),
            'prompt_args' => fn () => [],
            'response' => fn () => fake()->paragraphs(3, true),
            'overall_request_hash' => fn () => fake()->sha256(),
            'system_prompt_hash' => fn () => fake()->sha256(),
            'prompt_hash' => fn () => fake()->sha256(),
            'response_hash' => fn () => fake()->sha256(),
            'prompt_tokens' => fn () => fake()->numberBetween(100, 1000),
            'completion_tokens' => fn () => fake()->numberBetween(100, 500),
        ];
    }
}

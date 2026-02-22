<?php

namespace Database\Factories;

use App\Models\Prompt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromptVersion>
 */
class PromptVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prompt_id' => fn () => Prompt::factory(),
            'version' => fn () => 1,
            'content' => fn () => fake()->paragraphs(3, true),
            'change_notes' => fn () => null,
        ];
    }
}

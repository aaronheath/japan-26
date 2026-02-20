<?php

namespace Database\Factories;

use App\Enums\VenueType;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => fn () => City::factory(),
            'type' => fn () => fake()->randomElement(VenueType::cases()),
            'name' => fn () => fake()->company(),
            'description' => fn () => fake()->optional()->sentence(),
        ];
    }
}

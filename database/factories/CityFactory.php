<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => fn () => Country::factory(),
            'state_id' => fn () => State::factory(),
            'name' => fn () => fake()->city(),
            'population' => fn () => fake()->numberBetween(10000, 1000000),
            'timezone' => fn () => fake()->timezone(),
        ];
    }
}

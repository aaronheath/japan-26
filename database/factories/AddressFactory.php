<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
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
            'city_id' => fn () => City::factory(),
            'postcode' => fn () => fake()->postcode(),
            'line_1' => fn () => fake()->streetAddress(),
            'line_2' => fn () => fake()->optional()->secondaryAddress(),
            'line_3' => fn () => null,
        ];
    }
}

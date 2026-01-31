<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Day;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DayTravel>
 */
class DayTravelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day_id' => fn () => Day::factory(),
            'start_city_id' => fn () => City::factory(),
            'end_city_id' => fn () => City::factory(),
            'overnight' => fn () => fake()->boolean(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Day;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DayAccommodation>
 */
class DayAccommodationFactory extends Factory
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
            'venue_id' => fn () => Venue::factory(),
        ];
    }
}

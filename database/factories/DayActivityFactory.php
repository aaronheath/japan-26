<?php

namespace Database\Factories;

use App\Enums\DayActivities;
use App\Models\City;
use App\Models\Day;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DayActivity>
 */
class DayActivityFactory extends Factory
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
            'city_id' => fn () => City::factory(),
            'venue_id' => fn () => null,
            'type' => fn () => fake()->randomElement(DayActivities::cases()),
        ];
    }

    public function sightseeing(): static
    {
        return $this->state(fn () => [
            'type' => DayActivities::SIGHTSEEING,
        ]);
    }

    public function wrestling(): static
    {
        return $this->state(fn () => [
            'type' => DayActivities::WRESTLING,
        ]);
    }

    public function eating(): static
    {
        return $this->state(fn () => [
            'type' => DayActivities::EATING,
        ]);
    }
}

<?php

namespace App\Services\LLM;

use App\Models\City;
use App\Models\Day;
use App\Models\Project;
use App\Models\ProjectVersion;

class GenerateRequiredLLMInteractions
{
    protected Project $project;

    protected ProjectVersion $version;

    protected array $interactions = [
        'once_off' => [],
        'daily' => [],
    ];

    public static function make(): static
    {
        return new static;
    }

    public function project(Project $project)
    {
        $this->project = $project;
        $this->version = $project->latestVersion();

        return $this;
    }

    public function run()
    {
        $this->addDailyInteractions();

        return $this;
    }

    public function getInteractions(): array
    {
        return $this->interactions;
    }

    protected function addDailyInteractions()
    {
        $this->version->days->each(function ($day, $i) {
            $dayInteractions = [
                'day_id' => $day->id,
                'travel' => [],
                'activities' => [],
            ];

            if ($day->travel) {
                $dayInteractions['travel'] = [
                    'type' => $day->travel->startCity->state->country->id === $day->travel->endCity->state->country->id
                        ? 'domestic'
                        : 'international',
                    'start_city' => [
                        'id' => $day->travel->start_city_id,
                        'name' => $day->travel->startCity->name,
                        'state' => $day->travel->startCity->state->name,
                        'country' => $day->travel->startCity->state->country->name,
                    ],
                    'end_city' => [
                        'id' => $day->travel->end_city_id,
                        'name' => $day->travel->endCity->name,
                        'state' => $day->travel->endCity->state->name,
                        'country' => $day->travel->endCity->state->country->name,
                    ],
                    'overnight' => $day->travel->overnight,
                ];

                if (! $day->travel->overnight) {
                    // If not overnight, we may need to generate accommodation interactions
                    $dayInteractions['accommodation'] = [
                        'city' => [
                            'id' => $day->travel->end_city_id,
                            'name' => $day->travel->endCity->name,
                            'state' => $day->travel->endCity->state->name,
                            'country' => $day->travel->endCity->state->country->name,
                        ],
                        'nights' => $this->daysUntilNextTravel($day),
                    ];
                }

                // Check if previous day had an overnight travel, if so then we need to generate accommodation interactions
                if ($i > 0) {
                    $previousDay = $this->version->days[$i - 1];

                    if ($previousDay->travel?->overnight) {
                        $dayInteractions['accommodation'] = [
                            'city' => [
                                'id' => $previousDay->travel->end_city_id,
                                'name' => $previousDay->travel->endCity->name,
                                'state' => $previousDay->travel->endCity->state->name,
                                'country' => $previousDay->travel->endCity->state->country->name,
                            ],
                            'nights' => $this->daysUntilNextTravel($day),
                        ];
                    }
                }
            }

            //            ray($day->activities);

            if ($day->activities->isNotEmpty()) {
                $dayInteractions['activities'] = $day->activities->map(function ($activity) {
                    ray($this->inferCityForDay($activity->day)?->toArray());

                    $city = $activity->city ?: $this->inferCityForDay($activity->day);

                    return [
                        'type' => $activity->type,
                        'venue' => $activity->venue ? [
                            'id' => $activity->venue->id,
                            'name' => $activity->venue->name,
                            'city' => $activity->venue->city->name,
                            'state' => $activity->venue->city->state->name,
                            'country' => $activity->venue->city->state->country->name,
                        ] : null,
                        'city' => $city ? [
                            'id' => $city->id,
                            'name' => $city->name,
                            'state' => $city->state->name,
                            'country' => $city->state->country->name,
                        ] : null,
                    ];
                })->toArray();
            }

            $this->interactions['daily'][$i] = $dayInteractions;
        });
    }

    protected function daysUntilNextTravel(Day $day)
    {
        $nextDayWithTravel = $this
            ->version
            ->days()
            ->where('number', '>', $day->number)
            ->whereHas('travel')
            ->orderBy('number')
            ->first();

        return is_null($nextDayWithTravel)
            ? null
            : $nextDayWithTravel->number - $day->number;
    }

    protected function inferCityForDay(Day $day)
    {
        // If there's travel on this day, return the appropriate city
        if ($day->travel) {
            return $day->travel->overnight
                ? $day->travel->startCity
                : $day->travel->endCity;
        }

        // Otherwise find the most recent day that had travel and return the end city of that travel
        $mostRecentDayWithTravel = $this
            ->version
            ->days()
            ->where('number', '<', $day->number)
            ->whereHas('travel')
            ->orderBy('number', 'desc')
            ->first();

        return is_null($mostRecentDayWithTravel)
            ? null
            : $mostRecentDayWithTravel->travel->endCity;
    }
}

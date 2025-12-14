<?php

namespace App\Services\LLM;

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
        return new static();
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
            ];

            if($day->travel) {
                $dayInteractions['travel'] = [
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

                if(! $day->travel->overnight) {
                    // If not overnight, we may need to generate accommodation interactions
                    $dayInteractions['accommodation'] = [
                        'city' => [
                            'id' => $day->travel->end_city_id,
                            'name' => $day->travel->endCity->name,
                            'state' => $day->travel->endCity->state->name,
                            'country' => $day->travel->endCity->state->country->name,
                        ],
                        // TODO add nights
                    ];
                }

                // Check if previous day had an overnight travel, if so then we need to generate accommodation interactions
                if($i > 0) {
                    $previousDay = $this->version->days[$i - 1];

                    if ($previousDay->travel?->overnight) {
                        $dayInteractions['accommodation'] = [
                            'city' => [
                                'id' => $previousDay->travel->end_city_id,
                                'name' => $previousDay->travel->endCity->name,
                                'state' => $previousDay->travel->endCity->state->name,
                                'country' => $previousDay->travel->endCity->state->country->name,
                            ],
                            // TODO add nights
                        ];
                    }
                }
            }

            $this->interactions['daily'][$i] = $dayInteractions;
        });
    }
}

<?php

namespace Database\Seeders;

use App\Enums\DayActivities;
use App\Models\City;
use App\Models\Project;
use App\Models\ProjectVersion;
use Illuminate\Database\Seeder;

class Japan26Seeder extends Seeder
{
    protected Project $project;

    protected ProjectVersion $version;

    public function run(): void
    {
        $this->createProject();
        $this->createVersion();
        $this->createDays();
        $this->createDayTravel();
        $this->createDayActivities();
    }

    protected function createProject()
    {
        $this->project = Project::updateOrCreate(
            [
                'name' => 'Japan 2026',
            ],
            [
                'start_date' => '2026-08-01', // Saturday - Departure from Adelaide
                'end_date' => '2026-08-22', // Saturday - Arrival back in Adelaide
            ]
        );
    }

    protected function createVersion()
    {
        $this->version = $this->project->version()->create();
    }

    protected function createDays()
    {
        for ($i = 0; $i <= $this->project->duration(); $i++) {
            $this->version->days()->updateOrCreate(
                [
                    'number' => $i + 1,
                ],
                [
                    'date' => $this->project->start_date->copy()->addDays($i),
                ]
            );
        }
    }

    protected function createDayTravel()
    {
        $travelDays = [
            [
                // Day 1 - International Travel
                'start_city' => 'Adelaide',
                'end_city' => 'Bunkyo', // Tokyo - Korakuen Hall
            ],
            [
                // Day 2
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 3
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 4
                'start_city' => 'Bunkyo',
                'end_city' => 'Suita',
            ],
            [
                // Day 5
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 6
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 7
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 8
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 9
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 10
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 11
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 12
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 13
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 14
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 15
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 16
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 17
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 18
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 19
                'start_city' => '',
                'end_city' => '',
            ],
            [
                // Day 20 - International Travel (Overnight)
                'start_city' => 'Bunkyo',
                'end_city' => 'Adelaide',
                'overnight' => true,
            ],
        ];

        collect($travelDays)->each(function ($day, $i) {
            if (empty($day['start_city']) || empty($day['end_city'])) {
                return;
            }

            $startCity = City::where('name', $day['start_city'])->first();
            $endCity = City::where('name', $day['end_city'])->first();

            if (! $startCity || ! $endCity) {
                return;
            }

            $this->version->days()->where('number', $i + 1)->first()->travel()->updateOrCreate(
                [],
                [
                    'start_city_id' => $startCity->id,
                    'end_city_id' => $endCity->id,
                    'overnight' => $day['overnight'] ?? false,
                ]
            );
        });
    }

    protected function createDayActivities()
    {
        $activities = [
            // Day 1
            [],
            // Day 2
            [
                [
                    'type' => DayActivities::SIGHTSEEING,
                ],
            ],
            // Day 3
            [],
            // Day 4
            [],
            // Day 5
            [],
            // Day 6
            [],
            // Day 7
            [],
            // Day 8
            [],
            // Day 9
            [],
            // Day 10
            [],
            // Day 11
            [],
            // Day 12
            [],
            // Day 13
            [],
            // Day 14
            [],
            // Day 15
            [],
            // Day 16
            [],
            // Day 17
            [],
            // Day 18
            [],
            // Day 19
            [],
            // Day 20
            [],
        ];

        collect($activities)->each(function ($dayActivities, $i) {
            foreach ($dayActivities as $activity) {
                $this->version->days()->where('number', $i + 1)->first()->activities()->updateOrCreate(
                    [
                        'type' => $activity['type'],
                        'venue_id' => $activity['venue_id'] ?? null,
                        'city_id' => $activity['city_id'] ?? null,
                    ]
                );
            }
        });
    }
}

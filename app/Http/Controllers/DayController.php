<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Models\DayActivity;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DayController extends Controller
{
    protected ?Day $day;

    public function __invoke(Project $project, int $day): Response
    {
        $this->day = $project->latestVersion()->days()->where('number', $day)->first();

        abort_unless((bool) $this->day, 404);

        return Inertia::render('project/day', [
            'project' => $project,
            'tab' => request()->query('tab', 'overview'),
            'day' => [
                'id' => $this->day->id,
                'number' => $this->day->number,
                'date' => $this->day->date->format('Y-m-d'),
            ],
            'travel' => $this->travel(),
            'activities' => $this->activities(),
        ]);
    }

    /**
     * @return array{id: int, start_city: mixed, end_city: mixed, llm_call: array<string, mixed>|null}|array{}
     */
    protected function travel(): array
    {
        $travel = $this->day->travel;

        if (! $travel) {
            return [];
        }

        return [
            'id' => $travel->id,
            'start_city' => $travel->startCity->load('state'),
            'end_city' => $travel->endCity->load('state'),
            'llm_call' => $travel->latestLlmCall()?->only(['id', 'response', 'created_at', 'llm_provider_name']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function activities(): array
    {
        return $this->day->activities()->get()->map(function (DayActivity $activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->type,
                'city' => $activity->useCity()?->only(['id', 'name', 'country_code']) ?? null,
                'llm_call' => $activity->latestLlmCall()?->only(['id', 'response', 'created_at', 'llm_provider_name']) ?? null,
            ];
        })->toArray();
    }
}

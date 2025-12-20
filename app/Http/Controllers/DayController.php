<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Models\DayActivity;
use App\Models\Project;
use Illuminate\Http\Request;

class DayController extends Controller
{
    protected Day|null $day;

    public function __invoke(Project $project, int $day)
    {
        $this->day = $project->latestVersion()->days()->where('number', $day)->first();

        abort_unless((bool) $this->day, 404);

        $data = [
            'project' => $project,
            'tab' => request()->query('tab', 'overview'),
            'day' => $this->day,
            'travel' => $this->travel(),
            'activities' => $this->activities(),
        ];

        ray($data);

        return view('day', $data);
    }

    protected function travel()
    {
        $travel = $this->day->travel;

        if(!$travel) {
            return [];
        }

        return [
            'start_city' => $travel->startCity->only(['id', 'name', 'country_code']),
            'end_city' => $travel->endCity->only(['id', 'name', 'country_code']),
            'llm_call' => $travel->latestLlmCall()?->only(['id', 'response', 'created_at']),
        ];
    }

    protected function activities()
    {
        return $this->day->activities()->get()->map(function (DayActivity $activity) {
            return [
//                'id' => $activity->id,
                'type' => $activity->type,
//                'venue' => $activity->venue?->only(['id', 'name']) ?? null,
                'city' => $activity->useCity()?->only(['id', 'name', 'country_code']) ?? null,
                'llm_call' => $activity->latestLlmCall()?->only(['id', 'response', 'created_at']) ?? null,
            ];
        });
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\DayActivities;
use App\Models\Day;
use App\Models\Project;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function show(Project $project): View
    {
        $days = $project
            ->latestVersion()
            ->days()
            ->with(['travel', 'activities'])
            ->orderBy('number')
            ->get()
            ->map(fn (Day $day) => $this->mapDayData($day, $project));

        return view('project', [
            'project' => $project,
            'days' => $days,
            'activityTypes' => DayActivities::cases(),
        ]);
    }

    protected function mapDayData(Day $day, Project $project): array
    {
        $activities = [];

        foreach ($day->activities as $index => $activity) {
            $activities[$activity->type->value] = [
                'hasLlmCall' => $activity->latestLlmCall() !== null,
                'url' => route('project.day.show', [
                    'project' => $project,
                    'day' => $day->number,
                    'tab' => 'activity-'.$index,
                ]),
            ];
        }

        return [
            'number' => $day->number,
            'date' => $day->date,
            'travel' => $day->travel ? [
                'hasLlmCall' => $day->travel->latestLlmCall() !== null,
                'url' => route('project.day.show', [
                    'project' => $project,
                    'day' => $day->number,
                    'tab' => 'travel',
                ]),
            ] : null,
            'activities' => $activities,
        ];
    }
}

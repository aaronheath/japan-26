<?php

namespace App\Http\Controllers;

use App\Enums\DayActivities;
use App\Models\Day;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function show(Project $project): Response
    {
        $days = $project
            ->latestVersion()
            ->days()
            ->with(['travel', 'activities'])
            ->orderBy('number')
            ->get()
            ->map(fn (Day $day) => $this->mapDayData($day, $project));

        return Inertia::render('project/show', [
            'project' => $project,
            'days' => $days,
            'activityTypes' => collect(DayActivities::cases())->map(fn ($case) => $case->value),
        ]);
    }

    /**
     * @return array{number: int, date: mixed, travel: array{hasLlmCall: bool, url: string}|null, activities: array<string, array{hasLlmCall: bool, url: string}>}
     */
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

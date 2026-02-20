<?php

namespace App\Http\Controllers\Manage;

use App\Enums\DayActivities;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreDayActivityRequest;
use App\Http\Requests\Manage\UpdateDayActivityRequest;
use App\Models\City;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\Project;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DayActivityManagementController extends Controller
{
    public function index(Project $project): Response
    {
        $latestVersion = $project->latestVersion();

        $days = $latestVersion
            ? $latestVersion->days()
                ->with(['activities.venue', 'activities.city'])
                ->orderBy('number')
                ->get()
                ->map(fn (Day $day) => [
                    'id' => $day->id,
                    'number' => $day->number,
                    'date' => $day->date->format('Y-m-d'),
                    'activities' => $day->activities->map(fn (DayActivity $activity) => [
                        'id' => $activity->id,
                        'type' => $activity->type->value,
                        'venue_id' => $activity->venue_id,
                        'city_id' => $activity->city_id,
                        'venue_name' => $activity->venue?->name,
                        'city_name' => $activity->city?->name,
                    ])->all(),
                ])
            : collect();

        return Inertia::render('manage/project/activities', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'days' => $days,
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'venues' => Venue::query()->orderBy('name')->get(['id', 'name']),
            'activityTypes' => collect(DayActivities::cases())->map(fn (DayActivities $type) => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ]),
        ]);
    }

    public function store(StoreDayActivityRequest $request, Project $project): RedirectResponse
    {
        DayActivity::create($request->validated());

        return back();
    }

    public function update(UpdateDayActivityRequest $request, Project $project, DayActivity $activity): RedirectResponse
    {
        $activity->update($request->validated());

        return back();
    }

    public function destroy(Project $project, DayActivity $activity): RedirectResponse
    {
        $activity->delete();

        return back();
    }
}

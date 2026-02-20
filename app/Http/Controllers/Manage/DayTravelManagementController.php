<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreDayTravelRequest;
use App\Http\Requests\Manage\UpdateDayTravelRequest;
use App\Models\City;
use App\Models\DayTravel;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DayTravelManagementController extends Controller
{
    public function index(Project $project): Response
    {
        $latestVersion = $project->latestVersion();

        $days = $latestVersion
            ? $latestVersion->days()
                ->with(['travel.startCity', 'travel.endCity'])
                ->orderBy('number')
                ->get()
                ->map(fn ($day) => [
                    'id' => $day->id,
                    'number' => $day->number,
                    'date' => $day->date->format('Y-m-d'),
                    'travel' => $day->travel ? [
                        'id' => $day->travel->id,
                        'start_city_id' => $day->travel->start_city_id,
                        'end_city_id' => $day->travel->end_city_id,
                        'start_city_name' => $day->travel->startCity->name,
                        'end_city_name' => $day->travel->endCity->name,
                        'overnight' => (bool) $day->travel->overnight,
                    ] : null,
                ])
            : collect();

        return Inertia::render('manage/project/travel', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'days' => $days,
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreDayTravelRequest $request, Project $project): RedirectResponse
    {
        DayTravel::create($request->validated());

        return back();
    }

    public function update(UpdateDayTravelRequest $request, Project $project, DayTravel $travel): RedirectResponse
    {
        $travel->update($request->validated());

        return back();
    }

    public function destroy(Project $project, DayTravel $travel): RedirectResponse
    {
        $travel->delete();

        return back();
    }
}

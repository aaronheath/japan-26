<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreDayAccommodationRequest;
use App\Http\Requests\Manage\UpdateDayAccommodationRequest;
use App\Models\DayAccommodation;
use App\Models\Project;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DayAccommodationManagementController extends Controller
{
    public function index(Project $project): Response
    {
        $latestVersion = $project->latestVersion();

        $days = $latestVersion
            ? $latestVersion->days()
                ->with(['accommodation.venue'])
                ->orderBy('number')
                ->get()
                ->map(fn ($day) => [
                    'id' => $day->id,
                    'number' => $day->number,
                    'date' => $day->date->format('Y-m-d'),
                    'accommodation' => $day->accommodation ? [
                        'id' => $day->accommodation->id,
                        'venue_id' => $day->accommodation->venue_id,
                        'venue_name' => $day->accommodation->venue->name,
                    ] : null,
                ])
            : collect();

        return Inertia::render('manage/project/accommodations', [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'days' => $days,
            'venues' => Venue::query()->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(StoreDayAccommodationRequest $request, Project $project): RedirectResponse
    {
        DayAccommodation::create($request->validated());

        return back();
    }

    public function update(UpdateDayAccommodationRequest $request, Project $project, DayAccommodation $accommodation): RedirectResponse
    {
        $accommodation->update($request->validated());

        return back();
    }

    public function destroy(Project $project, DayAccommodation $accommodation): RedirectResponse
    {
        $accommodation->delete();

        return back();
    }
}

<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreProjectRequest;
use App\Http\Requests\Manage\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectVersionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('manage/projects', [
            'projects' => Project::query()
                ->withCount('versions')
                ->orderBy('name')
                ->get()
                ->map(fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date->format('Y-m-d'),
                    'end_date' => $project->end_date->format('Y-m-d'),
                    'versions_count' => $project->versions_count,
                    'duration' => $project->duration(),
                ]),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        $version = $project->versions()->create();

        $startDate = Carbon::parse($request->validated('start_date'));
        $endDate = Carbon::parse($request->validated('end_date'));
        $duration = (int) $startDate->diffInDays($endDate);

        for ($i = 0; $i <= $duration; $i++) {
            $version->days()->create([
                'number' => $i + 1,
                'date' => $startDate->copy()->addDays($i),
            ]);
        }

        return back();
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $newStartDate = Carbon::parse($request->validated('start_date'));
        $newEndDate = Carbon::parse($request->validated('end_date'));
        $datesChanged = ! $newStartDate->equalTo($project->start_date) || ! $newEndDate->equalTo($project->end_date);

        if ($datesChanged) {
            app(ProjectVersionService::class)->createVersionFromDateChange(
                $project,
                $newStartDate,
                $newEndDate,
                $request->boolean('move_last_days'),
            );
        }

        $project->update(['name' => $request->validated('name')]);

        return back();
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->versions()->each(function ($version) {
            $version->days()->each(function ($day) {
                $day->travel()->delete();
                $day->accommodation()->delete();
                $day->activities()->delete();
            });
            $version->days()->delete();
        });
        $project->versions()->delete();
        $project->delete();

        return back();
    }
}

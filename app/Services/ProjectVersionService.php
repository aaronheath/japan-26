<?php

namespace App\Services;

use App\Models\Day;
use App\Models\Project;
use App\Models\ProjectVersion;
use Carbon\Carbon;

class ProjectVersionService
{
    public function createVersionFromDateChange(
        Project $project,
        Carbon $newStartDate,
        Carbon $newEndDate,
        bool $moveLastDays = false,
    ): ProjectVersion {
        $oldVersion = $project->latestVersion();
        $oldDays = $oldVersion?->days()->with(['travel', 'activities', 'accommodation'])->orderBy('number')->get();

        $project->update([
            'start_date' => $newStartDate,
            'end_date' => $newEndDate,
        ]);

        $newVersion = $project->versions()->create();
        $newDuration = (int) $newStartDate->diffInDays($newEndDate);

        $newDays = collect();

        for ($i = 0; $i <= $newDuration; $i++) {
            $newDays->push($newVersion->days()->create([
                'number' => $i + 1,
                'date' => $newStartDate->copy()->addDays($i),
            ]));
        }

        if (! $oldDays || $oldDays->isEmpty()) {
            return $newVersion;
        }

        $this->copyDayData($oldDays, $newDays, $moveLastDays);

        return $newVersion;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Day>  $oldDays
     * @param  \Illuminate\Support\Collection<int, Day>  $newDays
     */
    protected function copyDayData(
        \Illuminate\Database\Eloquent\Collection $oldDays,
        \Illuminate\Support\Collection $newDays,
        bool $moveLastDays,
    ): void {
        $oldDaysByDate = $oldDays->keyBy(fn (Day $day) => $day->date->format('Y-m-d'));

        foreach ($newDays as $newDay) {
            $dateKey = $newDay->date->format('Y-m-d');

            if (! $oldDaysByDate->has($dateKey)) {
                continue;
            }

            $this->copyDayRelations($oldDaysByDate->get($dateKey), $newDay);
        }

        if (! $moveLastDays || $newDays->count() === $oldDays->count()) {
            return;
        }

        $this->moveLastDaysToEnd($oldDays, $newDays);
    }

    protected function copyDayRelations(Day $oldDay, Day $newDay): void
    {
        if ($oldDay->travel) {
            $newDay->travel()->create([
                'start_city_id' => $oldDay->travel->start_city_id,
                'end_city_id' => $oldDay->travel->end_city_id,
                'overnight' => $oldDay->travel->overnight,
            ]);
        }

        if ($oldDay->accommodation) {
            $newDay->accommodation()->create([
                'venue_id' => $oldDay->accommodation->venue_id,
            ]);
        }

        foreach ($oldDay->activities as $activity) {
            $newDay->activities()->create([
                'venue_id' => $activity->venue_id,
                'city_id' => $activity->city_id,
                'type' => $activity->type,
            ]);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Day>  $oldDays
     * @param  \Illuminate\Support\Collection<int, Day>  $newDays
     */
    protected function moveLastDaysToEnd(
        \Illuminate\Database\Eloquent\Collection $oldDays,
        \Illuminate\Support\Collection $newDays,
    ): void {
        $oldLastDay = $oldDays->last();
        $oldSecondLastDay = $oldDays->count() >= 2 ? $oldDays->get($oldDays->count() - 2) : null;

        $newLastDay = $newDays->last();
        $newSecondLastDay = $newDays->count() >= 2 ? $newDays->get($newDays->count() - 2) : null;

        if ($oldLastDay && $newLastDay && ! $newLastDay->travel) {
            $this->clearAndCopyDayRelations($oldLastDay, $newLastDay);
        }

        if ($oldSecondLastDay && $newSecondLastDay && ! $newSecondLastDay->travel) {
            $this->clearAndCopyDayRelations($oldSecondLastDay, $newSecondLastDay);
        }
    }

    protected function clearAndCopyDayRelations(Day $oldDay, Day $newDay): void
    {
        $newDay->travel()->delete();
        $newDay->accommodation()->delete();
        $newDay->activities()->delete();

        $this->copyDayRelations($oldDay, $newDay);
    }
}

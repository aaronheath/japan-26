<?php

namespace App\Services\LLM;

use App\Enums\DayActivities;
use App\Enums\RegenerationColumnType;
use App\Jobs\RegenerateLlmContent;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Models\LlmRegenerationBatch;
use App\Models\Project;
use App\Services\LLM\Generators\CitySightseeing;
use App\Services\LLM\Generators\TravelDomestic;
use App\Services\LLM\Generators\TravelInternational;
use Illuminate\Support\Facades\Bus;

class RegenerationService
{
    public function regenerateSingleTravel(Project $project, DayTravel $travel): LlmRegenerationBatch
    {
        $jobs = [$this->createTravelJob($travel)];

        return $this->dispatchBatch($project, 'single', 'travel', $jobs, "Regenerate Travel #{$travel->id}");
    }

    public function regenerateSingleActivity(Project $project, DayActivity $activity): LlmRegenerationBatch
    {
        $jobs = [$this->createActivityJob($activity)];

        return $this->dispatchBatch($project, 'single', $activity->type->value, $jobs, "Regenerate Activity #{$activity->id}");
    }

    public function regenerateDay(Project $project, Day $day): LlmRegenerationBatch
    {
        $jobs = $this->collectJobsForDay($day);

        return $this->dispatchBatch($project, 'day', null, $jobs, "Regenerate Day #{$day->number}");
    }

    public function regenerateColumn(Project $project, RegenerationColumnType $type): LlmRegenerationBatch
    {
        $jobs = $this->collectJobsForColumn($project, $type);

        return $this->dispatchBatch($project, 'column', $type->value, $jobs, "Regenerate Column: {$type->value}");
    }

    public function regenerateProject(Project $project): LlmRegenerationBatch
    {
        $jobs = $this->collectJobsForProject($project);

        return $this->dispatchBatch($project, 'project', null, $jobs, "Regenerate Project: {$project->name}");
    }

    /**
     * @param  array<RegenerateLlmContent>  $jobs
     */
    protected function dispatchBatch(
        Project $project,
        string $scope,
        ?string $generatorType,
        array $jobs,
        string $batchName,
    ): LlmRegenerationBatch {
        $batch = $this->createBatchRecord($project, $scope, $generatorType, count($jobs));

        if (empty($jobs)) {
            $batch->markAsCompleted();

            return $batch;
        }

        $laravelBatch = Bus::batch($jobs)
            ->then(fn () => $batch->markAsCompleted())
            ->catch(fn () => $batch->markAsFailed())
            ->finally(fn () => $batch->refresh())
            ->name($batchName)
            ->dispatch();

        $batch->update([
            'batch_id' => $laravelBatch->id,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        return $batch;
    }

    protected function createBatchRecord(
        Project $project,
        string $scope,
        ?string $generatorType,
        int $totalJobs,
    ): LlmRegenerationBatch {
        return LlmRegenerationBatch::create([
            'project_id' => $project->id,
            'scope' => $scope,
            'generator_type' => $generatorType,
            'total_jobs' => $totalJobs,
            'status' => 'pending',
        ]);
    }

    /**
     * @return array<RegenerateLlmContent>
     */
    protected function collectJobsForDay(Day $day): array
    {
        $jobs = [];

        if ($day->travel) {
            $jobs[] = $this->createTravelJob($day->travel);
        }

        foreach ($day->activities as $activity) {
            $jobs[] = $this->createActivityJob($activity);
        }

        return $jobs;
    }

    /**
     * @return array<RegenerateLlmContent>
     */
    protected function collectJobsForColumn(Project $project, RegenerationColumnType $type): array
    {
        $jobs = [];
        $version = $project->latestVersion();

        if ($type === RegenerationColumnType::Travel) {
            $travels = DayTravel::query()
                ->whereHas('day', fn ($q) => $q->where('project_version_id', $version->id))
                ->get();

            foreach ($travels as $travel) {
                $jobs[] = $this->createTravelJob($travel);
            }

            return $jobs;
        }

        $activityType = DayActivities::from($type->value);
        $activities = DayActivity::query()
            ->whereHas('day', fn ($q) => $q->where('project_version_id', $version->id))
            ->where('type', $activityType)
            ->get();

        foreach ($activities as $activity) {
            $jobs[] = $this->createActivityJob($activity);
        }

        return $jobs;
    }

    /**
     * @return array<RegenerateLlmContent>
     */
    protected function collectJobsForProject(Project $project): array
    {
        $jobs = [];
        $version = $project->latestVersion();
        $days = $version->days()->with(['travel', 'activities'])->get();

        foreach ($days as $day) {
            $jobs = array_merge($jobs, $this->collectJobsForDay($day));
        }

        return $jobs;
    }

    protected function createTravelJob(DayTravel $travel): RegenerateLlmContent
    {
        return new RegenerateLlmContent(
            DayTravel::class,
            $travel->id,
            $this->getTravelGeneratorClass($travel),
        );
    }

    protected function createActivityJob(DayActivity $activity): RegenerateLlmContent
    {
        return new RegenerateLlmContent(
            DayActivity::class,
            $activity->id,
            $this->getActivityGeneratorClass($activity),
        );
    }

    /**
     * @return class-string<TravelDomestic|TravelInternational>
     */
    protected function getTravelGeneratorClass(DayTravel $travel): string
    {
        $startCountry = $travel->startCity->country_id;
        $endCountry = $travel->endCity->country_id;

        return $startCountry === $endCountry
            ? TravelDomestic::class
            : TravelInternational::class;
    }

    /**
     * @return class-string<CitySightseeing>
     */
    protected function getActivityGeneratorClass(DayActivity $activity): string
    {
        return CitySightseeing::class;
    }
}

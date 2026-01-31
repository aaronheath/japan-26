<?php

namespace App\Services\LLM;

use App\Enums\DayActivities;
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
        $batch = LlmRegenerationBatch::create([
            'project_id' => $project->id,
            'scope' => 'single',
            'generator_type' => 'travel',
            'total_jobs' => 1,
            'status' => 'pending',
        ]);

        $generatorClass = $this->getTravelGeneratorClass($travel);

        $laravelBatch = Bus::batch([
            new RegenerateLlmContent(DayTravel::class, $travel->id, $generatorClass),
        ])
            ->then(fn () => $batch->markAsCompleted())
            ->catch(fn () => $batch->markAsFailed())
            ->finally(fn () => $batch->refresh())
            ->name("Regenerate Travel #{$travel->id}")
            ->dispatch();

        $batch->update([
            'batch_id' => $laravelBatch->id,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        return $batch;
    }

    public function regenerateSingleActivity(Project $project, DayActivity $activity): LlmRegenerationBatch
    {
        $batch = LlmRegenerationBatch::create([
            'project_id' => $project->id,
            'scope' => 'single',
            'generator_type' => $activity->type->value,
            'total_jobs' => 1,
            'status' => 'pending',
        ]);

        $generatorClass = $this->getActivityGeneratorClass($activity);

        $laravelBatch = Bus::batch([
            new RegenerateLlmContent(DayActivity::class, $activity->id, $generatorClass),
        ])
            ->then(fn () => $batch->markAsCompleted())
            ->catch(fn () => $batch->markAsFailed())
            ->finally(fn () => $batch->refresh())
            ->name("Regenerate Activity #{$activity->id}")
            ->dispatch();

        $batch->update([
            'batch_id' => $laravelBatch->id,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        return $batch;
    }

    public function regenerateDay(Project $project, Day $day): LlmRegenerationBatch
    {
        $jobs = [];

        if ($day->travel) {
            $generatorClass = $this->getTravelGeneratorClass($day->travel);
            $jobs[] = new RegenerateLlmContent(DayTravel::class, $day->travel->id, $generatorClass);
        }

        foreach ($day->activities as $activity) {
            $generatorClass = $this->getActivityGeneratorClass($activity);
            $jobs[] = new RegenerateLlmContent(DayActivity::class, $activity->id, $generatorClass);
        }

        $batch = LlmRegenerationBatch::create([
            'project_id' => $project->id,
            'scope' => 'day',
            'generator_type' => null,
            'total_jobs' => count($jobs),
            'status' => 'pending',
        ]);

        if (empty($jobs)) {
            $batch->markAsCompleted();

            return $batch;
        }

        $laravelBatch = Bus::batch($jobs)
            ->then(fn () => $batch->markAsCompleted())
            ->catch(fn () => $batch->markAsFailed())
            ->finally(fn () => $batch->refresh())
            ->name("Regenerate Day #{$day->number}")
            ->dispatch();

        $batch->update([
            'batch_id' => $laravelBatch->id,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        return $batch;
    }

    public function regenerateColumn(Project $project, string $type): LlmRegenerationBatch
    {
        $jobs = [];
        $version = $project->latestVersion();

        if ($type === 'travel') {
            $travels = DayTravel::query()
                ->whereHas('day', fn ($q) => $q->where('project_version_id', $version->id))
                ->get();

            foreach ($travels as $travel) {
                $generatorClass = $this->getTravelGeneratorClass($travel);
                $jobs[] = new RegenerateLlmContent(DayTravel::class, $travel->id, $generatorClass);
            }
        } else {
            $activityType = DayActivities::from($type);
            $activities = DayActivity::query()
                ->whereHas('day', fn ($q) => $q->where('project_version_id', $version->id))
                ->where('type', $activityType)
                ->get();

            foreach ($activities as $activity) {
                $generatorClass = $this->getActivityGeneratorClass($activity);
                $jobs[] = new RegenerateLlmContent(DayActivity::class, $activity->id, $generatorClass);
            }
        }

        $batch = LlmRegenerationBatch::create([
            'project_id' => $project->id,
            'scope' => 'column',
            'generator_type' => $type,
            'total_jobs' => count($jobs),
            'status' => 'pending',
        ]);

        if (empty($jobs)) {
            $batch->markAsCompleted();

            return $batch;
        }

        $laravelBatch = Bus::batch($jobs)
            ->then(fn () => $batch->markAsCompleted())
            ->catch(fn () => $batch->markAsFailed())
            ->finally(fn () => $batch->refresh())
            ->name("Regenerate Column: {$type}")
            ->dispatch();

        $batch->update([
            'batch_id' => $laravelBatch->id,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        return $batch;
    }

    public function regenerateProject(Project $project): LlmRegenerationBatch
    {
        $jobs = [];
        $version = $project->latestVersion();
        $days = $version->days()->with(['travel', 'activities'])->get();

        foreach ($days as $day) {
            if ($day->travel) {
                $generatorClass = $this->getTravelGeneratorClass($day->travel);
                $jobs[] = new RegenerateLlmContent(DayTravel::class, $day->travel->id, $generatorClass);
            }

            foreach ($day->activities as $activity) {
                $generatorClass = $this->getActivityGeneratorClass($activity);
                $jobs[] = new RegenerateLlmContent(DayActivity::class, $activity->id, $generatorClass);
            }
        }

        $batch = LlmRegenerationBatch::create([
            'project_id' => $project->id,
            'scope' => 'project',
            'generator_type' => null,
            'total_jobs' => count($jobs),
            'status' => 'pending',
        ]);

        if (empty($jobs)) {
            $batch->markAsCompleted();

            return $batch;
        }

        $laravelBatch = Bus::batch($jobs)
            ->then(fn () => $batch->markAsCompleted())
            ->catch(fn () => $batch->markAsFailed())
            ->finally(fn () => $batch->refresh())
            ->name("Regenerate Project: {$project->name}")
            ->dispatch();

        $batch->update([
            'batch_id' => $laravelBatch->id,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        return $batch;
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

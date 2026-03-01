<?php

namespace App\Http\Controllers;

use App\Enums\DayActivities;
use App\Enums\PromptType;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\Project;
use App\Models\Prompt;
use Inertia\Inertia;
use Inertia\Response;

class DayController extends Controller
{
    protected ?Day $day;

    public function __invoke(Project $project, int $day): Response
    {
        $this->day = $project->latestVersion()->days()->where('number', $day)->first();

        abort_unless((bool) $this->day, 404);

        return Inertia::render('project/day', [
            'project' => $project,
            'tab' => request()->query('tab', 'overview'),
            'day' => [
                'id' => $this->day->id,
                'number' => $this->day->number,
                'date' => $this->day->date->format('Y-m-d'),
            ],
            'travel' => $this->travel(),
            'activities' => $this->activities(),
            'travelPromptData' => $this->travelPromptData(),
            'activityPromptData' => $this->activityPromptData(),
        ]);
    }

    /**
     * @return array{id: int, start_city: mixed, end_city: mixed, llm_call: array<string, mixed>|null}|array{}
     */
    protected function travel(): array
    {
        $travel = $this->day->travel;

        if (! $travel) {
            return [];
        }

        return [
            'id' => $travel->id,
            'start_city' => $travel->startCity->load('state'),
            'end_city' => $travel->endCity->load('state'),
            'llm_call' => $travel->latestLlmCall()?->only(['id', 'response', 'created_at', 'llm_provider_name']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function activities(): array
    {
        return $this->day->activities()->get()->map(function (DayActivity $activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->type,
                'city' => $activity->useCity()?->only(['id', 'name', 'country_code']) ?? null,
                'llm_call' => $activity->latestLlmCall()?->only(['id', 'response', 'created_at', 'llm_provider_name']) ?? null,
            ];
        })->toArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function travelPromptData(): ?array
    {
        $travel = $this->day->travel;

        if (! $travel) {
            return null;
        }

        $slug = $travel->startCity->country_id === $travel->endCity->country_id
            ? 'travel-domestic-japan'
            : 'travel-international';

        return $this->buildPromptData($slug);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function activityPromptData(): array
    {
        $result = [];

        foreach ($this->day->activities as $activity) {
            $slug = $this->activityTypeToSlug($activity->type);
            $result[$activity->id] = $this->buildPromptData($slug);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildPromptData(string $taskSlug): ?array
    {
        $taskPrompt = Prompt::where('slug', $taskSlug)->with('activeVersion', 'systemPrompt.activeVersion')->first();

        if (! $taskPrompt || ! $taskPrompt->activeVersion) {
            return null;
        }

        $supplementary = Prompt::query()
            ->where('type', PromptType::Supplementary)
            ->where('day_id', $this->day->id)
            ->where('parent_prompt_id', $taskPrompt->id)
            ->with('activeVersion')
            ->first();

        return [
            'task_prompt_slug' => $taskSlug,
            'system_prompt_content' => $taskPrompt->systemPrompt?->activeVersion?->content,
            'task_prompt_content' => $taskPrompt->activeVersion->content,
            'supplementary_content' => $supplementary?->activeVersion?->content,
        ];
    }

    protected function activityTypeToSlug(DayActivities $type): string
    {
        return match ($type) {
            DayActivities::SIGHTSEEING => 'sightseeing',
            DayActivities::WRESTLING => 'wrestling',
            DayActivities::EATING => 'eating',
        };
    }
}

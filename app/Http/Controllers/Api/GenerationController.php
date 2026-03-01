<?php

namespace App\Http\Controllers\Api;

use App\Enums\PromptType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateFromDayRequest;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Models\Project;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Services\LLM\RegenerationService;
use Illuminate\Http\JsonResponse;

class GenerationController extends Controller
{
    public function __construct(
        protected RegenerationService $regenerationService
    ) {}

    public function generate(GenerateFromDayRequest $request, Project $project, Day $day): JsonResponse
    {
        $taskPrompt = Prompt::where('slug', $request->input('task_prompt_slug'))->firstOrFail();

        $this->updateTaskPromptIfChanged($taskPrompt, $request->input('task_prompt_content'));
        $this->upsertSupplementaryPrompt($taskPrompt, $day, $request->input('supplementary_content'));

        if ($request->input('type') === 'travel') {
            $travel = DayTravel::findOrFail($request->input('model_id'));
            $batch = $this->regenerationService->regenerateSingleTravel($project, $travel);
        } else {
            $activity = DayActivity::findOrFail($request->input('model_id'));
            $batch = $this->regenerationService->regenerateSingleActivity($project, $activity);
        }

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'total_jobs' => $batch->total_jobs,
        ]);
    }

    protected function updateTaskPromptIfChanged(Prompt $taskPrompt, ?string $newContent): void
    {
        if (is_null($newContent)) {
            return;
        }

        if ($taskPrompt->activeVersion->content === $newContent) {
            return;
        }

        $latestVersion = $taskPrompt->versions()->orderBy('version', 'desc')->first();
        $nextVersion = $latestVersion ? $latestVersion->version + 1 : 1;

        $version = PromptVersion::create([
            'prompt_id' => $taskPrompt->id,
            'version' => $nextVersion,
            'content' => $newContent,
            'change_notes' => 'Updated via day page generation',
        ]);

        $taskPrompt->update(['active_version_id' => $version->id]);
    }

    protected function upsertSupplementaryPrompt(Prompt $taskPrompt, Day $day, ?string $content): void
    {
        if (is_null($content)) {
            return;
        }

        $supplementary = Prompt::query()
            ->where('type', PromptType::Supplementary)
            ->where('day_id', $day->id)
            ->where('parent_prompt_id', $taskPrompt->id)
            ->first();

        if (! $supplementary) {
            if (trim($content) === '') {
                return;
            }

            $supplementary = Prompt::create([
                'name' => "{$taskPrompt->name} - Day {$day->number} Supplementary",
                'slug' => "{$taskPrompt->slug}-day-{$day->id}-supplementary",
                'description' => "Supplementary prompt for {$taskPrompt->name} on Day {$day->number}",
                'type' => PromptType::Supplementary,
                'day_id' => $day->id,
                'parent_prompt_id' => $taskPrompt->id,
            ]);

            $version = PromptVersion::create([
                'prompt_id' => $supplementary->id,
                'version' => 1,
                'content' => $content,
            ]);

            $supplementary->update(['active_version_id' => $version->id]);

            return;
        }

        if ($supplementary->activeVersion && $supplementary->activeVersion->content === $content) {
            return;
        }

        $latestVersion = $supplementary->versions()->orderBy('version', 'desc')->first();
        $nextVersion = $latestVersion ? $latestVersion->version + 1 : 1;

        $version = PromptVersion::create([
            'prompt_id' => $supplementary->id,
            'version' => $nextVersion,
            'content' => $content,
            'change_notes' => 'Updated via day page generation',
        ]);

        $supplementary->update(['active_version_id' => $version->id]);
    }
}

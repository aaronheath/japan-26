<?php

use App\Enums\LlmModels;
use App\Enums\PromptType;
use App\Models\Day;
use App\Models\LlmCall;
use App\Models\ProjectVersion;
use App\Models\Prompt;
use App\Models\PromptVersion;

it('can create a supplementary prompt with day and parent relationships', function () {
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $parentPrompt = Prompt::factory()->task()->create();
    $parentVersion = PromptVersion::factory()->create(['prompt_id' => $parentPrompt->id]);
    $parentPrompt->update(['active_version_id' => $parentVersion->id]);

    $supplementary = Prompt::create([
        'name' => 'Supplementary Test',
        'slug' => 'supplementary-test',
        'description' => 'Test supplementary prompt',
        'type' => PromptType::Supplementary,
        'day_id' => $day->id,
        'parent_prompt_id' => $parentPrompt->id,
    ]);

    expect($supplementary->day->id)->toBe($day->id)
        ->and($supplementary->parentPrompt->id)->toBe($parentPrompt->id)
        ->and($supplementary->type)->toBe(PromptType::Supplementary);
});

it('can access supplementary prompts from parent prompt', function () {
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $parentPrompt = Prompt::factory()->task()->create();

    Prompt::create([
        'name' => 'Supplementary 1',
        'slug' => 'supplementary-1',
        'description' => 'Test',
        'type' => PromptType::Supplementary,
        'day_id' => $day->id,
        'parent_prompt_id' => $parentPrompt->id,
    ]);

    expect($parentPrompt->supplementaryPrompts)->toHaveCount(1);
});

it('can access supplementary prompts from day', function () {
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $parentPrompt = Prompt::factory()->task()->create();

    Prompt::create([
        'name' => 'Supplementary Day',
        'slug' => 'supplementary-day',
        'description' => 'Test',
        'type' => PromptType::Supplementary,
        'day_id' => $day->id,
        'parent_prompt_id' => $parentPrompt->id,
    ]);

    expect($day->supplementaryPrompts)->toHaveCount(1);
});

it('enforces unique constraint on day_id and parent_prompt_id', function () {
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $parentPrompt = Prompt::factory()->task()->create();

    Prompt::create([
        'name' => 'First Supplementary',
        'slug' => 'first-supplementary',
        'description' => 'First',
        'type' => PromptType::Supplementary,
        'day_id' => $day->id,
        'parent_prompt_id' => $parentPrompt->id,
    ]);

    Prompt::create([
        'name' => 'Second Supplementary',
        'slug' => 'second-supplementary',
        'description' => 'Second',
        'type' => PromptType::Supplementary,
        'day_id' => $day->id,
        'parent_prompt_id' => $parentPrompt->id,
    ]);
})->throws(\Illuminate\Database\UniqueConstraintViolationException::class);

it('allows supplementary prompts for different days with same parent', function () {
    $version = ProjectVersion::factory()->create();
    $day1 = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $day2 = Day::factory()->for($version, 'version')->create(['number' => 2]);

    $parentPrompt = Prompt::factory()->task()->create();

    $supp1 = Prompt::create([
        'name' => 'Day 1 Supplementary',
        'slug' => 'day-1-supplementary',
        'description' => 'Day 1',
        'type' => PromptType::Supplementary,
        'day_id' => $day1->id,
        'parent_prompt_id' => $parentPrompt->id,
    ]);

    $supp2 = Prompt::create([
        'name' => 'Day 2 Supplementary',
        'slug' => 'day-2-supplementary',
        'description' => 'Day 2',
        'type' => PromptType::Supplementary,
        'day_id' => $day2->id,
        'parent_prompt_id' => $parentPrompt->id,
    ]);

    expect($supp1->exists)->toBeTrue()
        ->and($supp2->exists)->toBeTrue();
});

it('includes supplementary hash in overall request hash', function () {
    $llmCall = LlmCall::hashes(new LlmCall([
        'llm_provider_name' => LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH,
        'rendered_system_prompt' => 'System prompt',
        'rendered_task_prompt' => 'Task prompt',
        'rendered_supplementary_prompt' => 'Supplementary prompt',
    ]));

    $llmCallWithout = LlmCall::hashes(new LlmCall([
        'llm_provider_name' => LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH,
        'rendered_system_prompt' => 'System prompt',
        'rendered_task_prompt' => 'Task prompt',
    ]));

    expect($llmCall->supplementary_prompt_hash)->toBe(hash('sha256', 'Supplementary prompt'))
        ->and($llmCall->overall_request_hash)->not->toBe($llmCallWithout->overall_request_hash);
});

it('stores supplementary prompt version id on llm call', function () {
    $systemPrompt = Prompt::factory()->system()->create();
    $systemVersion = PromptVersion::factory()->create(['prompt_id' => $systemPrompt->id, 'content' => 'System']);
    $systemPrompt->update(['active_version_id' => $systemVersion->id]);

    $taskPrompt = Prompt::factory()->task()->create(['system_prompt_id' => $systemPrompt->id]);
    $taskVersion = PromptVersion::factory()->create(['prompt_id' => $taskPrompt->id, 'content' => 'Task']);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $suppPrompt = Prompt::factory()->supplementary()->create();
    $suppVersion = PromptVersion::factory()->create(['prompt_id' => $suppPrompt->id, 'content' => 'Supplementary']);
    $suppPrompt->update(['active_version_id' => $suppVersion->id]);

    $llmCall = LlmCall::create([
        'llm_provider_name' => LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH,
        'system_prompt_version_id' => $systemVersion->id,
        'task_prompt_version_id' => $taskVersion->id,
        'supplementary_prompt_version_id' => $suppVersion->id,
        'prompt_args' => [],
        'response' => 'Test response',
        'rendered_system_prompt' => 'System',
        'rendered_task_prompt' => 'Task',
        'rendered_supplementary_prompt' => 'Supplementary',
        'prompt_tokens' => 100,
        'completion_tokens' => 50,
    ]);

    expect($llmCall->supplementary_prompt_version_id)->toBe($suppVersion->id)
        ->and($llmCall->supplementaryPromptVersion->id)->toBe($suppVersion->id)
        ->and($llmCall->supplementary_prompt_hash)->toBe(hash('sha256', 'Supplementary'));

    $fresh = $llmCall->fresh();
    expect($fresh->rendered_supplementary_prompt)->toBeNull();
});

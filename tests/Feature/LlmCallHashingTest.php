<?php

use App\Enums\LlmModels;
use App\Models\LlmCall;
use App\Models\Prompt;
use App\Models\PromptVersion;

test('llm call can be created with rendered prompt virtual attributes', function () {
    $systemPrompt = Prompt::factory()->system()->create();
    $systemVersion = PromptVersion::factory()->create([
        'prompt_id' => $systemPrompt->id,
        'content' => 'You are a test system prompt.',
    ]);
    $systemPrompt->update(['active_version_id' => $systemVersion->id]);

    $taskPrompt = Prompt::factory()->task()->create(['system_prompt_id' => $systemPrompt->id]);
    $taskVersion = PromptVersion::factory()->create([
        'prompt_id' => $taskPrompt->id,
        'content' => 'Do something useful.',
    ]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $llmCall = LlmCall::create([
        'llm_provider_name' => LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH,
        'system_prompt_version_id' => $systemVersion->id,
        'task_prompt_version_id' => $taskVersion->id,
        'prompt_args' => [],
        'response' => 'Test response',
        'rendered_system_prompt' => 'You are a test system prompt.',
        'rendered_task_prompt' => 'Do something useful.',
        'prompt_tokens' => 100,
        'completion_tokens' => 50,
    ]);

    expect($llmCall->exists)->toBeTrue()
        ->and($llmCall->system_prompt_hash)->toBe(hash('sha256', 'You are a test system prompt.'))
        ->and($llmCall->prompt_hash)->toBe(hash('sha256', 'Do something useful.'))
        ->and($llmCall->overall_request_hash)->not->toBeNull()
        ->and($llmCall->response_hash)->toBe(hash('sha256', 'Test response'))
        ->and($llmCall->system_prompt_version_id)->toBe($systemVersion->id)
        ->and($llmCall->task_prompt_version_id)->toBe($taskVersion->id);

    $fresh = $llmCall->fresh();
    expect($fresh->rendered_system_prompt)->toBeNull()
        ->and($fresh->rendered_task_prompt)->toBeNull();
});

test('llm call hashes are used for cache lookup', function () {
    $systemPrompt = Prompt::factory()->system()->create();
    $systemVersion = PromptVersion::factory()->create([
        'prompt_id' => $systemPrompt->id,
        'content' => 'System prompt content.',
    ]);
    $systemPrompt->update(['active_version_id' => $systemVersion->id]);

    $taskPrompt = Prompt::factory()->task()->create(['system_prompt_id' => $systemPrompt->id]);
    $taskVersion = PromptVersion::factory()->create([
        'prompt_id' => $taskPrompt->id,
        'content' => 'Task prompt content.',
    ]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $renderedSystem = 'System prompt content.';
    $renderedTask = 'Task prompt content.';

    $llmCall = LlmCall::create([
        'llm_provider_name' => LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH,
        'system_prompt_version_id' => $systemVersion->id,
        'task_prompt_version_id' => $taskVersion->id,
        'prompt_args' => [],
        'response' => 'Cached response',
        'rendered_system_prompt' => $renderedSystem,
        'rendered_task_prompt' => $renderedTask,
        'prompt_tokens' => 100,
        'completion_tokens' => 50,
    ]);

    $projectedHashes = LlmCall::hashes(new LlmCall([
        'llm_provider_name' => LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH,
        'rendered_system_prompt' => $renderedSystem,
        'rendered_task_prompt' => $renderedTask,
    ]));

    $cached = LlmCall::where('overall_request_hash', $projectedHashes->overall_request_hash)->first();

    expect($cached)->not->toBeNull()
        ->and($cached->id)->toBe($llmCall->id);
});

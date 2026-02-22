<?php

use App\Enums\PromptType;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Models\User;

test('prompts index page loads with prompts', function () {
    $user = User::factory()->create();

    $prompt = Prompt::factory()->system()->create();

    $version = PromptVersion::factory()->create([
        'prompt_id' => $prompt->id,
        'version' => 1,
    ]);

    $prompt->update(['active_version_id' => $version->id]);

    $response = $this->actingAs($user)->get('/manage/prompts');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('manage/prompts')
            ->has('prompts')
            ->has('systemPrompts')
    );
});

test('can create a system prompt', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/manage/prompts', [
        'name' => 'Test System Prompt',
        'slug' => 'test-system-prompt',
        'description' => 'A test system prompt',
        'type' => 'system',
        'content' => 'You are a helpful assistant.',
    ]);

    $response->assertRedirect();

    $prompt = Prompt::where('slug', 'test-system-prompt')->first();

    expect($prompt)->not->toBeNull()
        ->and($prompt->name)->toBe('Test System Prompt')
        ->and($prompt->type)->toBe(PromptType::System)
        ->and($prompt->active_version_id)->not->toBeNull();

    $version = $prompt->activeVersion;

    expect($version->version)->toBe(1)
        ->and($version->content)->toBe('You are a helpful assistant.');
});

test('can create a task prompt linked to system prompt', function () {
    $user = User::factory()->create();

    $systemPrompt = Prompt::factory()->system()->create();
    $systemVersion = PromptVersion::factory()->create(['prompt_id' => $systemPrompt->id]);
    $systemPrompt->update(['active_version_id' => $systemVersion->id]);

    $response = $this->actingAs($user)->post('/manage/prompts', [
        'name' => 'Test Task Prompt',
        'slug' => 'test-task-prompt',
        'description' => 'A test task prompt',
        'type' => 'task',
        'content' => 'Do something for {{ $city->name }}.',
        'system_prompt_id' => $systemPrompt->id,
    ]);

    $response->assertRedirect();

    $prompt = Prompt::where('slug', 'test-task-prompt')->first();

    expect($prompt)->not->toBeNull()
        ->and($prompt->type)->toBe(PromptType::Task)
        ->and($prompt->system_prompt_id)->toBe($systemPrompt->id);
});

test('store validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/manage/prompts', []);

    $response->assertSessionHasErrors(['name', 'slug', 'type', 'content']);
});

test('store validates slug uniqueness', function () {
    $user = User::factory()->create();

    Prompt::factory()->create(['slug' => 'existing-slug']);

    $response = $this->actingAs($user)->post('/manage/prompts', [
        'name' => 'Test',
        'slug' => 'existing-slug',
        'type' => 'system',
        'content' => 'content',
    ]);

    $response->assertSessionHasErrors(['slug']);
});

test('store validates system_prompt_id required for task type', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/manage/prompts', [
        'name' => 'Test Task',
        'slug' => 'test-task',
        'type' => 'task',
        'content' => 'content',
    ]);

    $response->assertSessionHasErrors(['system_prompt_id']);
});

test('can update a prompt creating a new version', function () {
    $user = User::factory()->create();

    $prompt = Prompt::factory()->system()->create();

    $v1 = PromptVersion::factory()->create([
        'prompt_id' => $prompt->id,
        'version' => 1,
        'content' => 'Original content',
    ]);

    $prompt->update(['active_version_id' => $v1->id]);

    $response = $this->actingAs($user)->put("/manage/prompts/{$prompt->id}", [
        'content' => 'Updated content',
        'change_notes' => 'Fixed typo',
    ]);

    $response->assertRedirect();

    $prompt->refresh();

    expect($prompt->versions()->count())->toBe(2);

    $v2 = $prompt->activeVersion;

    expect($v2->version)->toBe(2)
        ->and($v2->content)->toBe('Updated content')
        ->and($v2->change_notes)->toBe('Fixed typo');
});

test('update increments version number correctly', function () {
    $user = User::factory()->create();

    $prompt = Prompt::factory()->system()->create();

    PromptVersion::factory()->create(['prompt_id' => $prompt->id, 'version' => 1]);
    PromptVersion::factory()->create(['prompt_id' => $prompt->id, 'version' => 2]);

    $v3Response = $this->actingAs($user)->put("/manage/prompts/{$prompt->id}", [
        'content' => 'Version 3 content',
    ]);

    $v3Response->assertRedirect();

    $latestVersion = $prompt->versions()->orderByDesc('version')->first();

    expect($latestVersion->version)->toBe(3);
});

test('can revert to a previous version', function () {
    $user = User::factory()->create();

    $prompt = Prompt::factory()->system()->create();

    $v1 = PromptVersion::factory()->create([
        'prompt_id' => $prompt->id,
        'version' => 1,
        'content' => 'Version 1',
    ]);

    $v2 = PromptVersion::factory()->create([
        'prompt_id' => $prompt->id,
        'version' => 2,
        'content' => 'Version 2',
    ]);

    $prompt->update(['active_version_id' => $v2->id]);

    $response = $this->actingAs($user)->post("/manage/prompts/{$prompt->id}/revert", [
        'version_id' => $v1->id,
    ]);

    $response->assertRedirect();

    $prompt->refresh();

    expect($prompt->active_version_id)->toBe($v1->id);
});

test('revert validates version belongs to prompt', function () {
    $user = User::factory()->create();

    $prompt1 = Prompt::factory()->system()->create();
    $prompt2 = Prompt::factory()->system()->create();

    $v1 = PromptVersion::factory()->create(['prompt_id' => $prompt2->id, 'version' => 1]);

    $response = $this->actingAs($user)->post("/manage/prompts/{$prompt1->id}/revert", [
        'version_id' => $v1->id,
    ]);

    $response->assertSessionHasErrors(['version_id']);
});

test('prompt has versions relationship', function () {
    $prompt = Prompt::factory()->system()->create();

    PromptVersion::factory()->create(['prompt_id' => $prompt->id, 'version' => 1]);
    PromptVersion::factory()->create(['prompt_id' => $prompt->id, 'version' => 2]);

    expect($prompt->versions)->toHaveCount(2);
});

test('prompt has system prompt relationship', function () {
    $systemPrompt = Prompt::factory()->system()->create();

    $taskPrompt = Prompt::factory()->task()->create([
        'system_prompt_id' => $systemPrompt->id,
    ]);

    expect($taskPrompt->systemPrompt->id)->toBe($systemPrompt->id);
});

test('system prompt has task prompts relationship', function () {
    $systemPrompt = Prompt::factory()->system()->create();

    Prompt::factory()->task()->create(['system_prompt_id' => $systemPrompt->id]);
    Prompt::factory()->task()->create(['system_prompt_id' => $systemPrompt->id]);

    expect($systemPrompt->taskPrompts)->toHaveCount(2);
});

test('prompt version belongs to prompt', function () {
    $prompt = Prompt::factory()->system()->create();
    $version = PromptVersion::factory()->create(['prompt_id' => $prompt->id]);

    expect($version->prompt->id)->toBe($prompt->id);
});

test('unauthenticated users cannot access prompts', function () {
    $this->get('/manage/prompts')->assertRedirect();
    $this->post('/manage/prompts', [])->assertRedirect();
});

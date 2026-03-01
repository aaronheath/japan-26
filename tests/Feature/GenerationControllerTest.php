<?php

use App\Enums\PromptType;
use App\Models\City;
use App\Models\Country;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Bus::fake();
});

it('requires authentication', function () {
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $this->postJson(route('api.generation.generate', [$project, $day]))
        ->assertUnauthorized();
});

it('validates required fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'model_id', 'task_prompt_slug']);
});

it('validates type must be travel or activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [
            'type' => 'invalid',
            'model_id' => 1,
            'task_prompt_slug' => 'sightseeing',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('dispatches generation for a travel item', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $startCity = City::factory()->for($country)->for($state)->create();
    $endCity = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $travel = DayTravel::factory()->for($day)->create([
        'start_city_id' => $startCity->id,
        'end_city_id' => $endCity->id,
    ]);

    $taskPrompt = Prompt::factory()->task()->create(['slug' => 'travel-domestic-japan']);
    $taskVersion = PromptVersion::factory()->create(['prompt_id' => $taskPrompt->id]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $response = $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [
            'type' => 'travel',
            'model_id' => $travel->id,
            'task_prompt_slug' => 'travel-domestic-japan',
        ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['batch_id', 'status', 'total_jobs']);

    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1);
});

it('dispatches generation for an activity item', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->for($day)->for($city)->sightseeing()->create();

    $taskPrompt = Prompt::factory()->task()->create(['slug' => 'sightseeing']);
    $taskVersion = PromptVersion::factory()->create(['prompt_id' => $taskPrompt->id]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $response = $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [
            'type' => 'activity',
            'model_id' => $activity->id,
            'task_prompt_slug' => 'sightseeing',
        ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['batch_id', 'status', 'total_jobs']);

    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1);
});

it('creates new prompt version when task content changes', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->for($day)->for($city)->sightseeing()->create();

    $taskPrompt = Prompt::factory()->task()->create(['slug' => 'sightseeing']);
    $taskVersion = PromptVersion::factory()->create([
        'prompt_id' => $taskPrompt->id,
        'version' => 1,
        'content' => 'Original content',
    ]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [
            'type' => 'activity',
            'model_id' => $activity->id,
            'task_prompt_slug' => 'sightseeing',
            'task_prompt_content' => 'Updated content',
        ]);

    $taskPrompt->refresh();

    expect($taskPrompt->activeVersion->content)->toBe('Updated content')
        ->and($taskPrompt->activeVersion->version)->toBe(2)
        ->and($taskPrompt->versions()->count())->toBe(2);
});

it('does not create new version when content is unchanged', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->for($day)->for($city)->sightseeing()->create();

    $taskPrompt = Prompt::factory()->task()->create(['slug' => 'sightseeing']);
    $taskVersion = PromptVersion::factory()->create([
        'prompt_id' => $taskPrompt->id,
        'version' => 1,
        'content' => 'Original content',
    ]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [
            'type' => 'activity',
            'model_id' => $activity->id,
            'task_prompt_slug' => 'sightseeing',
            'task_prompt_content' => 'Original content',
        ]);

    expect($taskPrompt->versions()->count())->toBe(1);
});

it('creates supplementary prompt and version', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->for($day)->for($city)->sightseeing()->create();

    $taskPrompt = Prompt::factory()->task()->create(['slug' => 'sightseeing']);
    $taskVersion = PromptVersion::factory()->create(['prompt_id' => $taskPrompt->id]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [
            'type' => 'activity',
            'model_id' => $activity->id,
            'task_prompt_slug' => 'sightseeing',
            'supplementary_content' => 'Focus on temples',
        ]);

    $supplementary = Prompt::where('type', PromptType::Supplementary)
        ->where('day_id', $day->id)
        ->where('parent_prompt_id', $taskPrompt->id)
        ->first();

    expect($supplementary)->not->toBeNull()
        ->and($supplementary->activeVersion->content)->toBe('Focus on temples');
});

it('updates existing supplementary prompt version', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->for($day)->for($city)->sightseeing()->create();

    $taskPrompt = Prompt::factory()->task()->create(['slug' => 'sightseeing']);
    $taskVersion = PromptVersion::factory()->create(['prompt_id' => $taskPrompt->id]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $supplementary = Prompt::create([
        'name' => 'Sightseeing - Day 1 Supplementary',
        'slug' => "sightseeing-day-{$day->id}-supplementary",
        'description' => 'Supplementary prompt',
        'type' => PromptType::Supplementary,
        'day_id' => $day->id,
        'parent_prompt_id' => $taskPrompt->id,
    ]);

    $suppVersion = PromptVersion::create([
        'prompt_id' => $supplementary->id,
        'version' => 1,
        'content' => 'Original supplementary',
    ]);
    $supplementary->update(['active_version_id' => $suppVersion->id]);

    $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [
            'type' => 'activity',
            'model_id' => $activity->id,
            'task_prompt_slug' => 'sightseeing',
            'supplementary_content' => 'Updated supplementary',
        ]);

    $supplementary->refresh();

    expect($supplementary->activeVersion->content)->toBe('Updated supplementary')
        ->and($supplementary->activeVersion->version)->toBe(2)
        ->and($supplementary->versions()->count())->toBe(2);
});

it('does not create supplementary when content is empty', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->for($day)->for($city)->sightseeing()->create();

    $taskPrompt = Prompt::factory()->task()->create(['slug' => 'sightseeing']);
    $taskVersion = PromptVersion::factory()->create(['prompt_id' => $taskPrompt->id]);
    $taskPrompt->update(['active_version_id' => $taskVersion->id]);

    $this->actingAs($user)
        ->postJson(route('api.generation.generate', [$project, $day]), [
            'type' => 'activity',
            'model_id' => $activity->id,
            'task_prompt_slug' => 'sightseeing',
            'supplementary_content' => '',
        ]);

    $supplementaryCount = Prompt::where('type', PromptType::Supplementary)
        ->where('day_id', $day->id)
        ->count();

    expect($supplementaryCount)->toBe(0);
});

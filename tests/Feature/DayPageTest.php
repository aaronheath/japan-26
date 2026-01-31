<?php

use App\Enums\LlmModels;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Models\LlmCall;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;

test('day page returns llm_provider_name in travel llm_call data', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->create(['project_id' => $project->id]);
    $day = Day::factory()->create(['project_version_id' => $version->id, 'number' => 1]);

    $travel = DayTravel::factory()->create(['day_id' => $day->id]);

    $llmCall = LlmCall::factory()->create([
        'llm_provider_name' => LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH,
    ]);

    $travel->llmCall()->attach($llmCall->id, ['generator' => 'App\\Services\\LLM\\TravelDomestic']);

    $response = $this->actingAs($user)->get("/project/{$project->id}/day/{$day->number}?tab=travel");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('project/day')
            ->has('travel.llm_call')
            ->where('travel.llm_call.llm_provider_name', LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH->value)
            ->has('travel.llm_call.id')
            ->has('travel.llm_call.created_at')
    );
});

test('day page returns llm_provider_name in activity llm_call data', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->create(['project_id' => $project->id]);
    $day = Day::factory()->create(['project_version_id' => $version->id, 'number' => 1]);

    $activity = DayActivity::factory()->sightseeing()->create(['day_id' => $day->id]);

    $llmCall = LlmCall::factory()->create([
        'llm_provider_name' => LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH,
    ]);

    $activity->llmCall()->attach($llmCall->id, ['generator' => 'App\\Services\\LLM\\CitySightseeing']);

    $response = $this->actingAs($user)->get("/project/{$project->id}/day/{$day->number}?tab=activity-0");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('project/day')
            ->has('activities.0.llm_call')
            ->where('activities.0.llm_call.llm_provider_name', LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH->value)
            ->has('activities.0.llm_call.id')
            ->has('activities.0.llm_call.created_at')
    );
});

test('day page handles null llm_call for travel gracefully', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->create(['project_id' => $project->id]);
    $day = Day::factory()->create(['project_version_id' => $version->id, 'number' => 1]);

    DayTravel::factory()->create(['day_id' => $day->id]);

    $response = $this->actingAs($user)->get("/project/{$project->id}/day/{$day->number}?tab=travel");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('project/day')
            ->where('travel.llm_call', null)
    );
});

test('day page handles null llm_call for activity gracefully', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->create(['project_id' => $project->id]);
    $day = Day::factory()->create(['project_version_id' => $version->id, 'number' => 1]);

    DayActivity::factory()->sightseeing()->create(['day_id' => $day->id]);

    $response = $this->actingAs($user)->get("/project/{$project->id}/day/{$day->number}?tab=activity-0");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('project/day')
            ->where('activities.0.llm_call', null)
    );
});

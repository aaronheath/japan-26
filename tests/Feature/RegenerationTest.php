<?php

use App\Models\City;
use App\Models\Country;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Models\LlmRegenerationBatch;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Bus::fake();
});

it('requires authentication for regeneration endpoints', function () {
    $project = Project::factory()->create();

    $this->postJson(route('api.regeneration.single', $project))
        ->assertUnauthorized();

    $this->postJson(route('api.regeneration.project', $project))
        ->assertUnauthorized();
});

it('can regenerate a single travel item', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $startCity = City::factory()->for($country)->for($state)->create();
    $endCity = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $travel = DayTravel::factory()
        ->for($day)
        ->create([
            'start_city_id' => $startCity->id,
            'end_city_id' => $endCity->id,
        ]);

    $response = $this->actingAs($user)
        ->postJson(route('api.regeneration.single', $project), [
            'type' => 'travel',
            'id' => $travel->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'processing',
        'total_jobs' => 1,
    ]);

    $this->assertDatabaseHas('llm_regeneration_batches', [
        'project_id' => $project->id,
        'scope' => 'single',
        'generator_type' => 'travel',
        'total_jobs' => 1,
    ]);

    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1);
});

it('can regenerate a single activity item', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()
        ->for($day)
        ->for($city)
        ->sightseeing()
        ->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.regeneration.single', $project), [
            'type' => 'activity',
            'id' => $activity->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'processing',
        'total_jobs' => 1,
    ]);

    $this->assertDatabaseHas('llm_regeneration_batches', [
        'project_id' => $project->id,
        'scope' => 'single',
        'generator_type' => 'sightseeing',
        'total_jobs' => 1,
    ]);
});

it('can regenerate all content for a day', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    DayTravel::factory()->for($day)->create([
        'start_city_id' => $city->id,
        'end_city_id' => $city->id,
    ]);

    DayActivity::factory()
        ->for($day)
        ->for($city)
        ->sightseeing()
        ->create();

    DayActivity::factory()
        ->for($day)
        ->for($city)
        ->eating()
        ->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.regeneration.day', [$project, $day]));

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'processing',
        'total_jobs' => 3,
    ]);

    $this->assertDatabaseHas('llm_regeneration_batches', [
        'project_id' => $project->id,
        'scope' => 'day',
        'total_jobs' => 3,
    ]);
});

it('can regenerate a column type', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();

    $day1 = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $day2 = Day::factory()->for($version, 'version')->create(['number' => 2]);

    DayActivity::factory()->for($day1)->for($city)->sightseeing()->create();
    DayActivity::factory()->for($day2)->for($city)->sightseeing()->create();
    DayActivity::factory()->for($day1)->for($city)->eating()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.regeneration.column', $project), [
            'type' => 'sightseeing',
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'processing',
        'total_jobs' => 2,
    ]);

    $this->assertDatabaseHas('llm_regeneration_batches', [
        'project_id' => $project->id,
        'scope' => 'column',
        'generator_type' => 'sightseeing',
        'total_jobs' => 2,
    ]);
});

it('can regenerate entire project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();

    $day1 = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $day2 = Day::factory()->for($version, 'version')->create(['number' => 2]);

    DayTravel::factory()->for($day1)->create([
        'start_city_id' => $city->id,
        'end_city_id' => $city->id,
    ]);

    DayActivity::factory()->for($day1)->for($city)->sightseeing()->create();
    DayActivity::factory()->for($day2)->for($city)->eating()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.regeneration.project', $project));

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'processing',
        'total_jobs' => 3,
    ]);

    $this->assertDatabaseHas('llm_regeneration_batches', [
        'project_id' => $project->id,
        'scope' => 'project',
        'total_jobs' => 3,
    ]);
});

it('returns correct job counts', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();

    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    DayTravel::factory()->for($day)->create([
        'start_city_id' => $city->id,
        'end_city_id' => $city->id,
    ]);

    DayActivity::factory()->for($day)->for($city)->sightseeing()->create();
    DayActivity::factory()->for($day)->for($city)->wrestling()->create();
    DayActivity::factory()->for($day)->for($city)->eating()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.regeneration.day', [$project, $day]));

    $response->assertSuccessful();
    $response->assertJson([
        'total_jobs' => 4,
    ]);
});

it('validates single regeneration request', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.regeneration.single', $project), [
            'type' => 'invalid',
            'id' => 1,
        ])
        ->assertUnprocessable();

    $this->actingAs($user)
        ->postJson(route('api.regeneration.single', $project), [
            'type' => 'travel',
        ])
        ->assertUnprocessable();
});

it('validates column regeneration request', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.regeneration.column', $project), [
            'type' => 'invalid',
        ])
        ->assertUnprocessable();

    $this->actingAs($user)
        ->postJson(route('api.regeneration.column', $project), [])
        ->assertUnprocessable();
});

it('can get regeneration status for project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    LlmRegenerationBatch::factory()
        ->for($project)
        ->processing()
        ->create(['total_jobs' => 5, 'completed_jobs' => 2]);

    $response = $this->actingAs($user)
        ->getJson(route('api.regeneration.status', $project));

    $response->assertSuccessful();
    $response->assertJson([
        'is_regenerating' => true,
    ]);
    $response->assertJsonStructure([
        'is_regenerating',
        'horizon_running',
        'active_batches',
        'recently_completed',
    ]);

    expect($response->json('active_batches'))->toHaveCount(1);
});

it('shows recently completed batches in status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    LlmRegenerationBatch::factory()
        ->for($project)
        ->completed()
        ->create(['completed_at' => now()->subSeconds(10)]);

    $response = $this->actingAs($user)
        ->getJson(route('api.regeneration.status', $project));

    $response->assertSuccessful();
    $response->assertJson([
        'is_regenerating' => false,
    ]);

    expect($response->json('recently_completed'))->toHaveCount(1);
});

it('does not show old completed batches in status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    LlmRegenerationBatch::factory()
        ->for($project)
        ->completed()
        ->create(['completed_at' => now()->subMinutes(5)]);

    $response = $this->actingAs($user)
        ->getJson(route('api.regeneration.status', $project));

    $response->assertSuccessful();

    expect($response->json('recently_completed'))->toHaveCount(0);
});

<?php

use App\Models\Day;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('displays the project landing page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    Day::factory()->for($version, 'version')->create(['number' => 1]);
    Day::factory()->for($version, 'version')->create(['number' => 2]);

    $response = $this->actingAs($user)->get(route('project.show', $project));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('project/show')
        ->has('project')
        ->has('days', 2)
        ->where('days.0.number', 1)
        ->where('days.1.number', 2)
    );
});

it('displays travel column when day has travel', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->actingAs($user)->get(route('project.show', $project));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('project/show')
        ->has('activityTypes')
    );
});

it('displays activity type columns', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->actingAs($user)->get(route('project.show', $project));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('project/show')
        ->has('activityTypes', 3)
        ->where('activityTypes.0', 'sightseeing')
        ->where('activityTypes.1', 'wrestling')
        ->where('activityTypes.2', 'eating')
    );
});

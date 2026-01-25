<?php

use App\Models\Day;
use App\Models\Project;
use App\Models\ProjectVersion;

it('displays the project landing page', function () {
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    Day::factory()->for($version, 'version')->create(['number' => 1]);
    Day::factory()->for($version, 'version')->create(['number' => 2]);

    $response = $this->get(route('project.show', $project));

    $response->assertSuccessful();
    $response->assertSee('Project Overview');
    $response->assertSee('Day 1');
    $response->assertSee('Day 2');
});

it('displays travel column when day has travel', function () {
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->get(route('project.show', $project));

    $response->assertSuccessful();
    $response->assertSee('Travel');
});

it('displays activity type columns', function () {
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->get(route('project.show', $project));

    $response->assertSuccessful();
    $response->assertSee('sightseeing');
    $response->assertSee('wrestling');
    $response->assertSee('eating');
});

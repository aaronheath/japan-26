<?php

use App\Models\Day;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;

it('requires authentication to list projects', function () {
    $this->get(route('manage.projects.index'))->assertRedirect(route('login'));
});

it('can list projects', function () {
    $user = User::factory()->create();
    Project::factory()->create();

    $response = $this->actingAs($user)->get(route('manage.projects.index'));

    $response->assertOk();
});

it('can create a project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.projects.store'), [
        'name' => 'Japan Trip 2026',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('projects', ['name' => 'Japan Trip 2026']);
});

it('creates a version when creating a project', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('manage.projects.store'), [
        'name' => 'Japan Trip 2026',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);

    $project = Project::where('name', 'Japan Trip 2026')->first();

    expect($project->versions()->count())->toBe(1);
});

it('creates days for the version when creating a project', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('manage.projects.store'), [
        'name' => 'Japan Trip 2026',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);

    $project = Project::where('name', 'Japan Trip 2026')->first();
    $version = $project->latestVersion();

    expect($version->days()->count())->toBe(10);
    expect($version->days()->orderBy('number')->first()->number)->toBe(1);
    expect($version->days()->orderBy('number')->first()->date->format('Y-m-d'))->toBe('2026-03-01');
});

it('requires a name to create a project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.projects.store'), [
        'name' => '',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires a unique name to create a project', function () {
    $user = User::factory()->create();
    Project::factory()->create(['name' => 'Japan Trip 2026']);

    $response = $this->actingAs($user)->post(route('manage.projects.store'), [
        'name' => 'Japan Trip 2026',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-10',
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires a start_date to create a project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.projects.store'), [
        'name' => 'Japan Trip 2026',
        'start_date' => '',
        'end_date' => '2026-03-10',
    ]);

    $response->assertSessionHasErrors('start_date');
});

it('requires an end_date to create a project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.projects.store'), [
        'name' => 'Japan Trip 2026',
        'start_date' => '2026-03-01',
        'end_date' => '',
    ]);

    $response->assertSessionHasErrors('end_date');
});

it('requires end_date to be after start_date', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.projects.store'), [
        'name' => 'Japan Trip 2026',
        'start_date' => '2026-03-10',
        'end_date' => '2026-03-01',
    ]);

    $response->assertSessionHasErrors('end_date');
});

it('can update a project name without date changes', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'name' => 'Old Name',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);
    ProjectVersion::factory()->for($project)->create();

    $response = $this->actingAs($user)->put(route('manage.projects.update', $project), [
        'name' => 'New Name',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New Name']);
    expect($project->fresh()->versions()->count())->toBe(1);
});

it('creates a new version when updating project dates', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'name' => 'Japan Trip',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);
    $version = ProjectVersion::factory()->for($project)->create();
    Day::factory()->for($version, 'version')->create(['number' => 1, 'date' => '2026-03-01']);

    $response = $this->actingAs($user)->put(route('manage.projects.update', $project), [
        'name' => 'Japan Trip',
        'start_date' => '2026-03-05',
        'end_date' => '2026-03-15',
    ]);

    $response->assertRedirect();
    expect($project->fresh()->versions()->count())->toBe(2);
});

it('requires a unique name to update a project', function () {
    $user = User::factory()->create();
    Project::factory()->create(['name' => 'Existing Trip']);
    $project = Project::factory()->create(['name' => 'My Trip']);

    $response = $this->actingAs($user)->put(route('manage.projects.update', $project), [
        'name' => 'Existing Trip',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);

    $response->assertSessionHasErrors('name');
});

it('can update a project with the same name', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'name' => 'Japan Trip',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);
    ProjectVersion::factory()->for($project)->create();

    $response = $this->actingAs($user)->put(route('manage.projects.update', $project), [
        'name' => 'Japan Trip',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
});

it('can delete a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->actingAs($user)->delete(route('manage.projects.destroy', $project));

    $response->assertRedirect();
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('project_versions', ['project_id' => $project->id]);
});

it('deletes all related data when deleting a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->actingAs($user)->delete(route('manage.projects.destroy', $project));

    $response->assertRedirect();
    $this->assertDatabaseMissing('days', ['id' => $day->id]);
});

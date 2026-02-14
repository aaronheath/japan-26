<?php

use App\Models\City;
use App\Models\Day;
use App\Models\DayTravel;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;

it('requires authentication to list travel', function () {
    $project = Project::factory()->create();

    $this->get(route('manage.project.travel.index', $project))->assertRedirect(route('login'));
});

it('can list travel for a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    ProjectVersion::factory()->for($project)->create();

    $response = $this->actingAs($user)->get(route('manage.project.travel.index', $project));

    $response->assertOk();
});

it('can create travel for a day', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $startCity = City::factory()->create();
    $endCity = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.travel.store', $project), [
        'day_id' => $day->id,
        'start_city_id' => $startCity->id,
        'end_city_id' => $endCity->id,
        'overnight' => false,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('day_travels', [
        'day_id' => $day->id,
        'start_city_id' => $startCity->id,
        'end_city_id' => $endCity->id,
    ]);
});

it('requires a valid day_id to create travel', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $startCity = City::factory()->create();
    $endCity = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.travel.store', $project), [
        'day_id' => 9999,
        'start_city_id' => $startCity->id,
        'end_city_id' => $endCity->id,
        'overnight' => false,
    ]);

    $response->assertSessionHasErrors('day_id');
});

it('requires a valid start_city_id to create travel', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $endCity = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.travel.store', $project), [
        'day_id' => $day->id,
        'start_city_id' => 9999,
        'end_city_id' => $endCity->id,
        'overnight' => false,
    ]);

    $response->assertSessionHasErrors('start_city_id');
});

it('requires a valid end_city_id to create travel', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $startCity = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.travel.store', $project), [
        'day_id' => $day->id,
        'start_city_id' => $startCity->id,
        'end_city_id' => 9999,
        'overnight' => false,
    ]);

    $response->assertSessionHasErrors('end_city_id');
});

it('requires overnight to be boolean', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $startCity = City::factory()->create();
    $endCity = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.travel.store', $project), [
        'day_id' => $day->id,
        'start_city_id' => $startCity->id,
        'end_city_id' => $endCity->id,
        'overnight' => 'not-a-boolean',
    ]);

    $response->assertSessionHasErrors('overnight');
});

it('cannot create duplicate travel for the same day', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $startCity = City::factory()->create();
    $endCity = City::factory()->create();

    DayTravel::factory()->create(['day_id' => $day->id]);

    $response = $this->actingAs($user)->post(route('manage.project.travel.store', $project), [
        'day_id' => $day->id,
        'start_city_id' => $startCity->id,
        'end_city_id' => $endCity->id,
        'overnight' => false,
    ]);

    $response->assertSessionHasErrors('day_id');
});

it('can update travel', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $travel = DayTravel::factory()->create(['day_id' => $day->id, 'overnight' => false]);
    $newStartCity = City::factory()->create();
    $newEndCity = City::factory()->create();

    $response = $this->actingAs($user)->put(route('manage.project.travel.update', [$project, $travel]), [
        'day_id' => $day->id,
        'start_city_id' => $newStartCity->id,
        'end_city_id' => $newEndCity->id,
        'overnight' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('day_travels', [
        'id' => $travel->id,
        'start_city_id' => $newStartCity->id,
        'end_city_id' => $newEndCity->id,
        'overnight' => true,
    ]);
});

it('can update travel with the same day_id', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $startCity = City::factory()->create();
    $endCity = City::factory()->create();
    $travel = DayTravel::factory()->create([
        'day_id' => $day->id,
        'start_city_id' => $startCity->id,
        'end_city_id' => $endCity->id,
    ]);

    $response = $this->actingAs($user)->put(route('manage.project.travel.update', [$project, $travel]), [
        'day_id' => $day->id,
        'start_city_id' => $startCity->id,
        'end_city_id' => $endCity->id,
        'overnight' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
});

it('can delete travel', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $travel = DayTravel::factory()->create(['day_id' => $day->id]);

    $response = $this->actingAs($user)->delete(route('manage.project.travel.destroy', [$project, $travel]));

    $response->assertRedirect();
    $this->assertDatabaseMissing('day_travels', ['id' => $travel->id]);
});

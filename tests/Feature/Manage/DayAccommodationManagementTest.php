<?php

use App\Models\Day;
use App\Models\DayAccommodation;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use App\Models\Venue;

it('requires authentication to list accommodations', function () {
    $project = Project::factory()->create();

    $this->get(route('manage.project.accommodations.index', $project))->assertRedirect(route('login'));
});

it('can list accommodations for a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    ProjectVersion::factory()->for($project)->create();

    $response = $this->actingAs($user)->get(route('manage.project.accommodations.index', $project));

    $response->assertOk();
});

it('can create an accommodation for a day', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $venue = Venue::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.accommodations.store', $project), [
        'day_id' => $day->id,
        'venue_id' => $venue->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('day_accommodations', [
        'day_id' => $day->id,
        'venue_id' => $venue->id,
    ]);
});

it('requires a valid day_id to create an accommodation', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $venue = Venue::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.accommodations.store', $project), [
        'day_id' => 9999,
        'venue_id' => $venue->id,
    ]);

    $response->assertSessionHasErrors('day_id');
});

it('requires a valid venue_id to create an accommodation', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->actingAs($user)->post(route('manage.project.accommodations.store', $project), [
        'day_id' => $day->id,
        'venue_id' => 9999,
    ]);

    $response->assertSessionHasErrors('venue_id');
});

it('cannot create duplicate accommodation for the same day', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $venue = Venue::factory()->create();

    DayAccommodation::factory()->create(['day_id' => $day->id]);

    $response = $this->actingAs($user)->post(route('manage.project.accommodations.store', $project), [
        'day_id' => $day->id,
        'venue_id' => $venue->id,
    ]);

    $response->assertSessionHasErrors('day_id');
});

it('can update an accommodation', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $accommodation = DayAccommodation::factory()->create(['day_id' => $day->id]);
    $newVenue = Venue::factory()->create();

    $response = $this->actingAs($user)->put(route('manage.project.accommodations.update', [$project, $accommodation]), [
        'day_id' => $day->id,
        'venue_id' => $newVenue->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('day_accommodations', [
        'id' => $accommodation->id,
        'venue_id' => $newVenue->id,
    ]);
});

it('can update accommodation with the same day_id', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $venue = Venue::factory()->create();
    $accommodation = DayAccommodation::factory()->create([
        'day_id' => $day->id,
        'venue_id' => $venue->id,
    ]);

    $response = $this->actingAs($user)->put(route('manage.project.accommodations.update', [$project, $accommodation]), [
        'day_id' => $day->id,
        'venue_id' => $venue->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
});

it('can delete an accommodation', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $accommodation = DayAccommodation::factory()->create(['day_id' => $day->id]);

    $response = $this->actingAs($user)->delete(route('manage.project.accommodations.destroy', [$project, $accommodation]));

    $response->assertRedirect();
    $this->assertDatabaseMissing('day_accommodations', ['id' => $accommodation->id]);
});

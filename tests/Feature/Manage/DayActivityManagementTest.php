<?php

use App\Enums\DayActivities;
use App\Models\City;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use App\Models\Venue;

it('requires authentication to list activities', function () {
    $project = Project::factory()->create();

    $this->get(route('manage.project.activities.index', $project))->assertRedirect(route('login'));
});

it('can list activities for a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    ProjectVersion::factory()->for($project)->create();

    $response = $this->actingAs($user)->get(route('manage.project.activities.index', $project));

    $response->assertOk();
});

it('can create an activity for a day with a city', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => $day->id,
        'city_id' => $city->id,
        'venue_id' => null,
        'type' => DayActivities::SIGHTSEEING->value,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('day_activities', [
        'day_id' => $day->id,
        'city_id' => $city->id,
        'type' => DayActivities::SIGHTSEEING->value,
    ]);
});

it('can create an activity for a day with a venue', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $venue = Venue::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => $day->id,
        'city_id' => null,
        'venue_id' => $venue->id,
        'type' => DayActivities::WRESTLING->value,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('day_activities', [
        'day_id' => $day->id,
        'venue_id' => $venue->id,
        'type' => DayActivities::WRESTLING->value,
    ]);
});

it('allows multiple activities per day', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $city = City::factory()->create();

    DayActivity::factory()->create(['day_id' => $day->id, 'type' => DayActivities::SIGHTSEEING]);

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => $day->id,
        'city_id' => $city->id,
        'venue_id' => null,
        'type' => DayActivities::EATING->value,
    ]);

    $response->assertRedirect();
    expect(DayActivity::where('day_id', $day->id)->count())->toBe(2);
});

it('requires a valid day_id to create an activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => 9999,
        'city_id' => $city->id,
        'type' => DayActivities::SIGHTSEEING->value,
    ]);

    $response->assertSessionHasErrors('day_id');
});

it('requires a valid type to create an activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => $day->id,
        'city_id' => $city->id,
        'type' => 'invalid_type',
    ]);

    $response->assertSessionHasErrors('type');
});

it('requires a type to create an activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => $day->id,
        'city_id' => $city->id,
        'type' => '',
    ]);

    $response->assertSessionHasErrors('type');
});

it('validates city_id when provided', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => $day->id,
        'city_id' => 9999,
        'type' => DayActivities::SIGHTSEEING->value,
    ]);

    $response->assertSessionHasErrors('city_id');
});

it('validates venue_id when provided', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => $day->id,
        'venue_id' => 9999,
        'type' => DayActivities::SIGHTSEEING->value,
    ]);

    $response->assertSessionHasErrors('venue_id');
});

it('can update an activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->sightseeing()->create(['day_id' => $day->id]);
    $newCity = City::factory()->create();

    $response = $this->actingAs($user)->put(route('manage.project.activities.update', [$project, $activity]), [
        'day_id' => $day->id,
        'city_id' => $newCity->id,
        'venue_id' => null,
        'type' => DayActivities::EATING->value,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('day_activities', [
        'id' => $activity->id,
        'city_id' => $newCity->id,
        'type' => DayActivities::EATING->value,
    ]);
});

it('can delete an activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->create(['day_id' => $day->id]);

    $response = $this->actingAs($user)->delete(route('manage.project.activities.destroy', [$project, $activity]));

    $response->assertRedirect();
    $this->assertDatabaseMissing('day_activities', ['id' => $activity->id]);
});

it('accepts all valid activity types', function (DayActivities $type) {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $version = ProjectVersion::factory()->for($project)->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.project.activities.store', $project), [
        'day_id' => $day->id,
        'city_id' => $city->id,
        'type' => $type->value,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('day_activities', [
        'day_id' => $day->id,
        'type' => $type->value,
    ]);
})->with([
    'sightseeing' => DayActivities::SIGHTSEEING,
    'wrestling' => DayActivities::WRESTLING,
    'eating' => DayActivities::EATING,
]);

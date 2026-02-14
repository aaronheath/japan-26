<?php

use App\Enums\VenueType;
use App\Models\City;
use App\Models\DayAccommodation;
use App\Models\DayActivity;
use App\Models\User;
use App\Models\Venue;

it('requires authentication to list venues', function () {
    $this->get(route('manage.venues.index'))->assertRedirect(route('login'));
});

it('can list venues', function () {
    $user = User::factory()->create();
    Venue::factory()->create();

    $response = $this->actingAs($user)->get(route('manage.venues.index'));

    $response->assertOk();
});

it('can create a venue', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Hotel->value,
        'name' => 'Grand Hyatt Tokyo',
        'description' => 'A luxury hotel in Roppongi',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('venues', ['name' => 'Grand Hyatt Tokyo', 'type' => VenueType::Hotel->value]);
});

it('can create a venue without a description', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Restaurant->value,
        'name' => 'Sushi Dai',
        'description' => null,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('venues', ['name' => 'Sushi Dai']);
});

it('requires a name to create a venue', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Hotel->value,
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires a valid city_id to create a venue', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => 9999,
        'type' => VenueType::Hotel->value,
        'name' => 'Test Venue',
    ]);

    $response->assertSessionHasErrors('city_id');
});

it('requires a valid type to create a venue', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => 'invalid_type',
        'name' => 'Test Venue',
    ]);

    $response->assertSessionHasErrors('type');
});

it('can update a venue', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create(['name' => 'Old Name']);
    $newCity = City::factory()->create();

    $response = $this->actingAs($user)->put(route('manage.venues.update', $venue), [
        'city_id' => $newCity->id,
        'type' => VenueType::Restaurant->value,
        'name' => 'New Name',
        'description' => 'Updated description',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'New Name', 'type' => VenueType::Restaurant->value]);
});

it('can delete a venue without dependents', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();

    $response = $this->actingAs($user)->delete(route('manage.venues.destroy', $venue));

    $response->assertRedirect();
    $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
});

it('cannot delete a venue used in activities', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    DayActivity::factory()->create(['venue_id' => $venue->id]);

    $response = $this->actingAs($user)->delete(route('manage.venues.destroy', $venue));

    $response->assertRedirect();
    $response->assertSessionHasErrors('venue');
    $this->assertDatabaseHas('venues', ['id' => $venue->id]);
});

it('cannot delete a venue used in accommodations', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    DayAccommodation::factory()->create(['venue_id' => $venue->id]);

    $response = $this->actingAs($user)->delete(route('manage.venues.destroy', $venue));

    $response->assertRedirect();
    $response->assertSessionHasErrors('venue');
    $this->assertDatabaseHas('venues', ['id' => $venue->id]);
});

<?php

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\Venue;

it('requires authentication to list cities', function () {
    $this->get(route('manage.cities.index'))->assertRedirect(route('login'));
});

it('can list cities', function () {
    $user = User::factory()->create();
    City::factory()->create();

    $response = $this->actingAs($user)->get(route('manage.cities.index'));

    $response->assertOk();
});

it('can create a city', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.cities.store'), [
        'country_id' => $country->id,
        'state_id' => $state->id,
        'name' => 'Tokyo',
        'population' => 14000000,
        'timezone' => 'Asia/Tokyo',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cities', ['name' => 'Tokyo', 'timezone' => 'Asia/Tokyo']);
});

it('can create a city without a state', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.cities.store'), [
        'country_id' => $country->id,
        'state_id' => null,
        'name' => 'Singapore',
        'population' => null,
        'timezone' => 'Asia/Singapore',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cities', ['name' => 'Singapore', 'state_id' => null]);
});

it('requires a name to create a city', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.cities.store'), [
        'country_id' => $country->id,
        'name' => '',
        'timezone' => 'Asia/Tokyo',
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires a valid country_id to create a city', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.cities.store'), [
        'country_id' => 9999,
        'name' => 'Tokyo',
        'timezone' => 'Asia/Tokyo',
    ]);

    $response->assertSessionHasErrors('country_id');
});

it('requires a valid timezone to create a city', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.cities.store'), [
        'country_id' => $country->id,
        'name' => 'Tokyo',
        'timezone' => 'Not/A/Timezone',
    ]);

    $response->assertSessionHasErrors('timezone');
});

it('requires a timezone to create a city', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.cities.store'), [
        'country_id' => $country->id,
        'name' => 'Tokyo',
        'timezone' => '',
    ]);

    $response->assertSessionHasErrors('timezone');
});

it('can update a city', function () {
    $user = User::factory()->create();
    $city = City::factory()->create(['name' => 'Tokyo']);
    $newCountry = Country::factory()->create();

    $response = $this->actingAs($user)->put(route('manage.cities.update', $city), [
        'country_id' => $newCountry->id,
        'state_id' => null,
        'name' => 'Osaka',
        'population' => 2700000,
        'timezone' => 'Asia/Tokyo',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cities', ['id' => $city->id, 'name' => 'Osaka', 'population' => 2700000]);
});

it('can delete a city without dependents', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->delete(route('manage.cities.destroy', $city));

    $response->assertRedirect();
    $this->assertDatabaseMissing('cities', ['id' => $city->id]);
});

it('cannot delete a city with venues', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();
    Venue::factory()->create(['city_id' => $city->id]);

    $response = $this->actingAs($user)->delete(route('manage.cities.destroy', $city));

    $response->assertRedirect();
    $response->assertSessionHasErrors('city');
    $this->assertDatabaseHas('cities', ['id' => $city->id]);
});

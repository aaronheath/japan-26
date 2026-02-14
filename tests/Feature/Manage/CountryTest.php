<?php

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;

it('requires authentication to list countries', function () {
    $this->get(route('manage.countries.index'))->assertRedirect(route('login'));
});

it('can list countries', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->get(route('manage.countries.index'));

    $response->assertOk();
});

it('can create a country', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.countries.store'), [
        'name' => 'Japan',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('countries', ['name' => 'Japan']);
});

it('requires a name to create a country', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.countries.store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires a unique name to create a country', function () {
    $user = User::factory()->create();
    Country::factory()->create(['name' => 'Japan']);

    $response = $this->actingAs($user)->post(route('manage.countries.store'), [
        'name' => 'Japan',
    ]);

    $response->assertSessionHasErrors('name');
});

it('can update a country', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create(['name' => 'Japan']);

    $response = $this->actingAs($user)->put(route('manage.countries.update', $country), [
        'name' => 'Australia',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('countries', ['id' => $country->id, 'name' => 'Australia']);
});

it('can update a country with the same name', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create(['name' => 'Japan']);

    $response = $this->actingAs($user)->put(route('manage.countries.update', $country), [
        'name' => 'Japan',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
});

it('requires a unique name to update a country', function () {
    $user = User::factory()->create();
    Country::factory()->create(['name' => 'Australia']);
    $country = Country::factory()->create(['name' => 'Japan']);

    $response = $this->actingAs($user)->put(route('manage.countries.update', $country), [
        'name' => 'Australia',
    ]);

    $response->assertSessionHasErrors('name');
});

it('can delete a country without dependents', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->delete(route('manage.countries.destroy', $country));

    $response->assertRedirect();
    $this->assertDatabaseMissing('countries', ['id' => $country->id]);
});

it('cannot delete a country with states', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();
    State::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->delete(route('manage.countries.destroy', $country));

    $response->assertRedirect();
    $response->assertSessionHasErrors('country');
    $this->assertDatabaseHas('countries', ['id' => $country->id]);
});

it('cannot delete a country with cities', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();
    City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->delete(route('manage.countries.destroy', $country));

    $response->assertRedirect();
    $response->assertSessionHasErrors('country');
    $this->assertDatabaseHas('countries', ['id' => $country->id]);
});

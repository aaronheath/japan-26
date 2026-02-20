<?php

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;

it('requires authentication to list states', function () {
    $this->get(route('manage.states.index'))->assertRedirect(route('login'));
});

it('can list states', function () {
    $user = User::factory()->create();
    State::factory()->create();

    $response = $this->actingAs($user)->get(route('manage.states.index'));

    $response->assertOk();
});

it('can create a state', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.states.store'), [
        'country_id' => $country->id,
        'name' => 'Tokyo',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('states', ['name' => 'Tokyo', 'country_id' => $country->id]);
});

it('requires a name to create a state', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.states.store'), [
        'country_id' => $country->id,
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires a valid country_id to create a state', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.states.store'), [
        'country_id' => 9999,
        'name' => 'Tokyo',
    ]);

    $response->assertSessionHasErrors('country_id');
});

it('can update a state', function () {
    $user = User::factory()->create();
    $state = State::factory()->create(['name' => 'Tokyo']);
    $newCountry = Country::factory()->create();

    $response = $this->actingAs($user)->put(route('manage.states.update', $state), [
        'country_id' => $newCountry->id,
        'name' => 'Osaka',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('states', ['id' => $state->id, 'name' => 'Osaka', 'country_id' => $newCountry->id]);
});

it('requires a valid country_id to update a state', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    $response = $this->actingAs($user)->put(route('manage.states.update', $state), [
        'country_id' => 9999,
        'name' => 'Osaka',
    ]);

    $response->assertSessionHasErrors('country_id');
});

it('can delete a state without dependents', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();

    $response = $this->actingAs($user)->delete(route('manage.states.destroy', $state));

    $response->assertRedirect();
    $this->assertDatabaseMissing('states', ['id' => $state->id]);
});

it('cannot delete a state with cities', function () {
    $user = User::factory()->create();
    $state = State::factory()->create();
    City::factory()->create(['state_id' => $state->id]);

    $response = $this->actingAs($user)->delete(route('manage.states.destroy', $state));

    $response->assertRedirect();
    $response->assertSessionHasErrors('state');
    $this->assertDatabaseHas('states', ['id' => $state->id]);
});

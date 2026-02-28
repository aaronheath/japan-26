<?php

use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\Venue;

it('requires authentication to list addresses', function () {
    $this->get(route('manage.addresses.index'))->assertRedirect(route('login'));
});

it('can list addresses', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    Address::factory()->create([
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $venue->city->country_id,
        'city_id' => $venue->city_id,
    ]);

    $response = $this->actingAs($user)->get(route('manage.addresses.index'));

    $response->assertOk();
});

it('can create an address with an addressable', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'state_id' => $state->id]);

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'state_id' => $state->id,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
        'line_2' => 'Imperial Palace',
        'line_3' => null,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('address', [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'line_1' => '1-1 Chiyoda',
        'postcode' => '100-0001',
    ]);
});

it('can create an address without a state', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'state_id' => null,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
        'line_2' => null,
        'line_3' => null,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('address', ['line_1' => '1-1 Chiyoda', 'state_id' => null]);
});

it('requires a country_id to create an address', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => 1,
        'country_id' => null,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
    ]);

    $response->assertSessionHasErrors('country_id');
});

it('requires a valid country_id to create an address', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => 1,
        'country_id' => 9999,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
    ]);

    $response->assertSessionHasErrors('country_id');
});

it('requires a city_id to create an address', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => 1,
        'country_id' => $country->id,
        'city_id' => null,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
    ]);

    $response->assertSessionHasErrors('city_id');
});

it('requires a postcode to create an address', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => 1,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'postcode' => '',
        'line_1' => '1-1 Chiyoda',
    ]);

    $response->assertSessionHasErrors('postcode');
});

it('requires line_1 to create an address', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => 1,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '',
    ]);

    $response->assertSessionHasErrors('line_1');
});

it('can update an address', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);
    $address = Address::factory()->create([
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'line_1' => 'Old Address',
    ]);

    $response = $this->actingAs($user)->put(route('manage.addresses.update', $address), [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'state_id' => null,
        'city_id' => $city->id,
        'postcode' => '200-0002',
        'line_1' => 'New Address',
        'line_2' => null,
        'line_3' => null,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('address', ['id' => $address->id, 'line_1' => 'New Address', 'postcode' => '200-0002']);
});

it('can delete an address whose addressable no longer exists', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);
    $address = Address::factory()->create([
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $city->id,
    ]);

    // Delete the venue so the addressable relationship returns null
    $venue->delete();

    $response = $this->actingAs($user)->delete(route('manage.addresses.destroy', $address));

    $response->assertRedirect();
    $this->assertDatabaseMissing('address', ['id' => $address->id]);
});

it('includes attached_to_url for addresses linked to venues', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $address = Address::factory()->create([
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $venue->city->country_id,
        'city_id' => $venue->city_id,
    ]);

    $response = $this->actingAs($user)->get(route('manage.addresses.index'));

    $response->assertOk();

    $addresses = $response->original->getData()['page']['props']['addresses'];
    $found = collect($addresses)->firstWhere('id', $address->id);

    expect($found['attached_to_url'])->toBe(route('manage.venues.index'));
});

it('does not include attached_to_url for unattached addresses', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);
    $address = Address::factory()->create([
        'addressable_type' => null,
        'addressable_id' => null,
        'country_id' => $country->id,
        'city_id' => $city->id,
    ]);

    $response = $this->actingAs($user)->get(route('manage.addresses.index'));

    $response->assertOk();

    $addresses = $response->original->getData()['page']['props']['addresses'];
    $found = collect($addresses)->firstWhere('id', $address->id);

    expect($found['attached_to_url'])->toBeNull();
});

it('cannot delete an address that is attached to an entity', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);
    $address = Address::factory()->create([
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $city->id,
    ]);

    $response = $this->actingAs($user)->delete(route('manage.addresses.destroy', $address));

    $response->assertRedirect();
    $response->assertSessionHasErrors('address');
    $this->assertDatabaseHas('address', ['id' => $address->id]);
});

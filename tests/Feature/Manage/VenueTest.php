<?php

use App\Enums\VenueType;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\DayAccommodation;
use App\Models\DayActivity;
use App\Models\State;
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

it('can create a venue with a new address', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);
    $addrCity = City::factory()->create(['country_id' => $country->id, 'state_id' => $state->id]);

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Hotel->value,
        'name' => 'Grand Hyatt Tokyo',
        'description' => 'A luxury hotel in Roppongi',
        'address_mode' => 'new',
        'address' => [
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $addrCity->id,
            'postcode' => '106-0032',
            'line_1' => '6-10-3 Roppongi',
            'line_2' => 'Minato-ku',
            'line_3' => null,
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('venues', ['name' => 'Grand Hyatt Tokyo', 'type' => VenueType::Hotel->value]);
    $this->assertDatabaseHas('address', [
        'line_1' => '6-10-3 Roppongi',
        'postcode' => '106-0032',
        'addressable_type' => Venue::class,
    ]);
});

it('can create a venue without a description', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();
    $country = Country::factory()->create();
    $addrCity = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Restaurant->value,
        'name' => 'Sushi Dai',
        'description' => null,
        'address_mode' => 'new',
        'address' => [
            'country_id' => $country->id,
            'city_id' => $addrCity->id,
            'postcode' => '104-0045',
            'line_1' => '5-2-1 Tsukiji',
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('venues', ['name' => 'Sushi Dai']);
});

it('can create a venue by selecting an existing unattached address', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();
    $country = Country::factory()->create();
    $addrCity = City::factory()->create(['country_id' => $country->id]);

    $address = Address::factory()->create([
        'addressable_type' => null,
        'addressable_id' => null,
        'country_id' => $country->id,
        'city_id' => $addrCity->id,
        'line_1' => 'Existing Address',
    ]);

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Hotel->value,
        'name' => 'Hotel With Existing Address',
        'address_mode' => 'select',
        'address_id' => $address->id,
    ]);

    $response->assertRedirect();

    $venue = Venue::where('name', 'Hotel With Existing Address')->first();
    $this->assertNotNull($venue);
    $this->assertDatabaseHas('address', [
        'id' => $address->id,
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
    ]);
});

it('requires address_mode when creating a venue', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Hotel->value,
        'name' => 'Test Venue',
    ]);

    $response->assertSessionHasErrors('address_mode');
});

it('requires address fields when creating with address_mode new', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Hotel->value,
        'name' => 'Test Venue',
        'address_mode' => 'new',
        'address' => [
            'country_id' => null,
            'city_id' => null,
            'postcode' => null,
            'line_1' => null,
        ],
    ]);

    $response->assertSessionHasErrors(['address.country_id', 'address.city_id', 'address.postcode', 'address.line_1']);
});

it('requires address_id when creating with address_mode select', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Hotel->value,
        'name' => 'Test Venue',
        'address_mode' => 'select',
        'address_id' => null,
    ]);

    $response->assertSessionHasErrors('address_id');
});

it('requires a name to create a venue', function () {
    $user = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => $city->id,
        'type' => VenueType::Hotel->value,
        'name' => '',
        'address_mode' => 'new',
    ]);

    $response->assertSessionHasErrors('name');
});

it('requires a valid city_id to create a venue', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('manage.venues.store'), [
        'city_id' => 9999,
        'type' => VenueType::Hotel->value,
        'name' => 'Test Venue',
        'address_mode' => 'new',
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
        'address_mode' => 'new',
    ]);

    $response->assertSessionHasErrors('type');
});

it('can update a venue and its address', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create(['name' => 'Old Name']);
    $newCity = City::factory()->create();
    $country = Country::factory()->create();
    $addrCity = City::factory()->create(['country_id' => $country->id]);

    $address = Address::factory()->create([
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $addrCity->id,
        'line_1' => 'Old Address',
    ]);

    $response = $this->actingAs($user)->put(route('manage.venues.update', $venue), [
        'city_id' => $newCity->id,
        'type' => VenueType::Restaurant->value,
        'name' => 'New Name',
        'description' => 'Updated description',
        'address' => [
            'country_id' => $country->id,
            'city_id' => $addrCity->id,
            'postcode' => '200-0002',
            'line_1' => 'New Address',
            'line_2' => null,
            'line_3' => null,
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'New Name', 'type' => VenueType::Restaurant->value]);
    $this->assertDatabaseHas('address', ['id' => $address->id, 'line_1' => 'New Address', 'postcode' => '200-0002']);
});

it('creates an address when updating a venue without one', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $addrCity = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->put(route('manage.venues.update', $venue), [
        'city_id' => $venue->city_id,
        'type' => $venue->type->value,
        'name' => $venue->name,
        'address' => [
            'country_id' => $country->id,
            'city_id' => $addrCity->id,
            'postcode' => '100-0001',
            'line_1' => 'Brand New Address',
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('address', [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'line_1' => 'Brand New Address',
    ]);
});

it('detaches address when deleting a venue', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $addrCity = City::factory()->create(['country_id' => $country->id]);

    $address = Address::factory()->create([
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $addrCity->id,
    ]);

    $response = $this->actingAs($user)->delete(route('manage.venues.destroy', $venue));

    $response->assertRedirect();
    $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    $this->assertDatabaseHas('address', [
        'id' => $address->id,
        'addressable_type' => null,
        'addressable_id' => null,
    ]);
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

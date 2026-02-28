<?php

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Http;

it('requires authentication to access autocomplete', function () {
    $this->getJson('/api/address-lookup/autocomplete?query=tokyo')
        ->assertUnauthorized();
});

it('requires authentication to access place details', function () {
    $this->getJson('/api/address-lookup/place/ChIJN1t_tDeuEmsRUsoyG83frY4')
        ->assertUnauthorized();
});

it('validates minimum query length for autocomplete', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/address-lookup/autocomplete?query=ab')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('query');
});

it('requires query parameter for autocomplete', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/address-lookup/autocomplete')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('query');
});

it('returns autocomplete suggestions', function () {
    $user = User::factory()->create();

    Http::fake([
        'places.googleapis.com/v1/places:autocomplete' => Http::response([
            'suggestions' => [
                [
                    'placePrediction' => [
                        'placeId' => 'ChIJ51cu8IcbXWARiRtXIothAS4',
                        'text' => ['text' => 'Tokyo, Japan'],
                    ],
                ],
                [
                    'placePrediction' => [
                        'placeId' => 'ChIJm7QJx95bGmAR4sZPAKOaHwU',
                        'text' => ['text' => 'Tokyo Station, Marunouchi, Chiyoda City, Tokyo, Japan'],
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/address-lookup/autocomplete?query=tokyo');

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['place_id' => 'ChIJ51cu8IcbXWARiRtXIothAS4'])
        ->assertJsonFragment(['description' => 'Tokyo, Japan']);
});

it('returns empty array when google api returns no results', function () {
    $user = User::factory()->create();

    Http::fake([
        'places.googleapis.com/v1/places:autocomplete' => Http::response([
            'suggestions' => [],
        ]),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/address-lookup/autocomplete?query=xyznonexistent');

    $response->assertOk()
        ->assertJsonCount(0);
});

it('returns place details with structured address data', function () {
    $user = User::factory()->create();

    Http::fake([
        'places.googleapis.com/v1/places/ChIJ51cu8IcbXWARiRtXIothAS4' => Http::response([
            'formattedAddress' => '1-1 Marunouchi, Chiyoda City, Tokyo 100-0005, Japan',
            'addressComponents' => [
                ['longText' => '1', 'shortText' => '1', 'types' => ['street_number']],
                ['longText' => '1 Marunouchi', 'shortText' => '1 Marunouchi', 'types' => ['route']],
                ['longText' => 'Chiyoda City', 'shortText' => 'Chiyoda City', 'types' => ['locality']],
                ['longText' => 'Tokyo', 'shortText' => 'Tokyo', 'types' => ['administrative_area_level_1']],
                ['longText' => 'Japan', 'shortText' => 'JP', 'types' => ['country']],
                ['longText' => '100-0005', 'shortText' => '100-0005', 'types' => ['postal_code']],
            ],
            'location' => [
                'latitude' => 35.6812362,
                'longitude' => 139.7671248,
            ],
        ]),
        'maps.googleapis.com/maps/api/timezone/*' => Http::response([
            'status' => 'OK',
            'timeZoneId' => 'Asia/Tokyo',
        ]),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/address-lookup/place/ChIJ51cu8IcbXWARiRtXIothAS4');

    $response->assertOk()
        ->assertJsonFragment(['line_1' => '1 1 Marunouchi'])
        ->assertJsonFragment(['postcode' => '100-0005'])
        ->assertJsonFragment(['latitude' => 35.6812362])
        ->assertJsonFragment(['longitude' => 139.7671248]);

    $this->assertDatabaseHas('countries', ['name' => 'Japan']);
    $this->assertDatabaseHas('states', ['name' => 'Tokyo']);
    $this->assertDatabaseHas('cities', ['name' => 'Chiyoda City', 'timezone' => 'Asia/Tokyo']);
});

it('finds existing geo records instead of creating duplicates', function () {
    $user = User::factory()->create();
    $country = Country::factory()->create(['name' => 'Japan']);
    $state = State::factory()->create(['name' => 'Tokyo', 'country_id' => $country->id]);
    $city = City::factory()->create(['name' => 'Chiyoda City', 'country_id' => $country->id, 'state_id' => $state->id]);

    Http::fake([
        'places.googleapis.com/v1/places/ChIJ51cu8IcbXWARiRtXIothAS4' => Http::response([
            'formattedAddress' => '1-1 Marunouchi, Chiyoda City, Tokyo 100-0005, Japan',
            'addressComponents' => [
                ['longText' => '1', 'shortText' => '1', 'types' => ['street_number']],
                ['longText' => '1 Marunouchi', 'shortText' => '1 Marunouchi', 'types' => ['route']],
                ['longText' => 'Chiyoda City', 'shortText' => 'Chiyoda City', 'types' => ['locality']],
                ['longText' => 'Tokyo', 'shortText' => 'Tokyo', 'types' => ['administrative_area_level_1']],
                ['longText' => 'Japan', 'shortText' => 'JP', 'types' => ['country']],
                ['longText' => '100-0005', 'shortText' => '100-0005', 'types' => ['postal_code']],
            ],
            'location' => [
                'latitude' => 35.6812362,
                'longitude' => 139.7671248,
            ],
        ]),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/address-lookup/place/ChIJ51cu8IcbXWARiRtXIothAS4');

    $response->assertOk()
        ->assertJsonFragment(['country_id' => $country->id])
        ->assertJsonFragment(['state_id' => $state->id])
        ->assertJsonFragment(['city_id' => $city->id]);

    $this->assertDatabaseCount('countries', 1);
    $this->assertDatabaseCount('states', 1);
    $this->assertDatabaseCount('cities', 1);
});

it('creates missing geo records when they do not exist', function () {
    $user = User::factory()->create();

    Http::fake([
        'places.googleapis.com/v1/places/some-place-id' => Http::response([
            'formattedAddress' => '1-1 Kita, Osaka 530-0001, Japan',
            'addressComponents' => [
                ['longText' => '1', 'shortText' => '1', 'types' => ['street_number']],
                ['longText' => '1 Kita', 'shortText' => '1 Kita', 'types' => ['route']],
                ['longText' => 'Osaka', 'shortText' => 'Osaka', 'types' => ['locality']],
                ['longText' => 'Osaka', 'shortText' => 'Osaka', 'types' => ['administrative_area_level_1']],
                ['longText' => 'Japan', 'shortText' => 'JP', 'types' => ['country']],
                ['longText' => '530-0001', 'shortText' => '530-0001', 'types' => ['postal_code']],
            ],
            'location' => [
                'latitude' => 34.7024854,
                'longitude' => 135.4959506,
            ],
        ]),
        'maps.googleapis.com/maps/api/timezone/*' => Http::response([
            'status' => 'OK',
            'timeZoneId' => 'Asia/Tokyo',
        ]),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/address-lookup/place/some-place-id');

    $response->assertOk();

    $this->assertDatabaseHas('countries', ['name' => 'Japan']);
    $this->assertDatabaseHas('states', ['name' => 'Osaka']);
    $this->assertDatabaseHas('cities', ['name' => 'Osaka', 'timezone' => 'Asia/Tokyo']);
});

it('can store an address with latitude and longitude', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
        'latitude' => 35.6812362,
        'longitude' => 139.7671248,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('address', [
        'line_1' => '1-1 Chiyoda',
        'latitude' => 35.6812362,
        'longitude' => 139.7671248,
    ]);
});

it('can store an address without latitude and longitude', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
        'latitude' => null,
        'longitude' => null,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('address', [
        'line_1' => '1-1 Chiyoda',
        'latitude' => null,
        'longitude' => null,
    ]);
});

it('validates latitude range', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
        'latitude' => 91,
        'longitude' => 139,
    ]);

    $response->assertSessionHasErrors('latitude');
});

it('validates longitude range', function () {
    $user = User::factory()->create();
    $venue = Venue::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $response = $this->actingAs($user)->post(route('manage.addresses.store'), [
        'addressable_type' => Venue::class,
        'addressable_id' => $venue->id,
        'country_id' => $country->id,
        'city_id' => $city->id,
        'postcode' => '100-0001',
        'line_1' => '1-1 Chiyoda',
        'latitude' => 35,
        'longitude' => 181,
    ]);

    $response->assertSessionHasErrors('longitude');
});

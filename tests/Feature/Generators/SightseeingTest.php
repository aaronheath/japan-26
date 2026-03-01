<?php

use App\Models\City;
use App\Models\Country;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\ProjectVersion;
use App\Models\State;
use App\Services\LLM\Generators\Sightseeing;

it('returns the correct prompt slug', function () {
    $generator = Sightseeing::make();

    $reflection = new ReflectionMethod($generator, 'promptSlug');

    expect($reflection->invoke($generator))->toBe('sightseeing');
});

it('returns correct prompt args with activity', function () {
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1, 'date' => '2026-04-01']);
    $activity = DayActivity::factory()->for($day)->for($city)->sightseeing()->create();

    $generator = Sightseeing::make()->activity($activity);

    $reflection = new ReflectionMethod($generator, 'promptArgs');
    $args = $reflection->invoke($generator);

    expect($args)->toHaveKeys(['city', 'date'])
        ->and($args['city']->id)->toBe($city->id)
        ->and($args['date'])->toBe('2026-04-01');
});

it('returns correct prompt args with city only', function () {
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();

    $generator = Sightseeing::make()->city($city);

    $reflection = new ReflectionMethod($generator, 'promptArgs');
    $args = $reflection->invoke($generator);

    expect($args)->toHaveKeys(['city'])
        ->and($args)->not->toHaveKey('date')
        ->and($args['city']->id)->toBe($city->id);
});

it('syncs to activity and city models', function () {
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->for($day)->for($city)->sightseeing()->create();

    $generator = Sightseeing::make()->activity($activity);

    $reflection = new ReflectionMethod($generator, 'syncToModels');
    $models = $reflection->invoke($generator);

    expect($models)->toHaveCount(2)
        ->and($models[0]->id)->toBe($activity->id)
        ->and($models[1]->id)->toBe($city->id);
});

it('accepts a day for supplementary prompt support', function () {
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);

    $generator = Sightseeing::make()->forDay($day);

    $reflection = new ReflectionProperty($generator, 'day');
    $dayValue = $reflection->getValue($generator);

    expect($dayValue->id)->toBe($day->id);
});

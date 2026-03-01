<?php

use App\Models\City;
use App\Models\Country;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\ProjectVersion;
use App\Models\State;
use App\Services\LLM\Generators\Eating;

it('returns the correct prompt slug', function () {
    $generator = Eating::make();

    $reflection = new ReflectionMethod($generator, 'promptSlug');

    expect($reflection->invoke($generator))->toBe('eating');
});

it('returns correct prompt args with activity', function () {
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1, 'date' => '2026-04-01']);
    $activity = DayActivity::factory()->for($day)->for($city)->eating()->create();

    $generator = Eating::make()->activity($activity);

    $reflection = new ReflectionMethod($generator, 'promptArgs');
    $args = $reflection->invoke($generator);

    expect($args)->toHaveKeys(['city', 'date'])
        ->and($args['city']->id)->toBe($city->id)
        ->and($args['date'])->toBe('2026-04-01');
});

it('syncs to activity and city models', function () {
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create();
    $city = City::factory()->for($country)->for($state)->create();
    $version = ProjectVersion::factory()->create();
    $day = Day::factory()->for($version, 'version')->create(['number' => 1]);
    $activity = DayActivity::factory()->for($day)->for($city)->eating()->create();

    $generator = Eating::make()->activity($activity);

    $reflection = new ReflectionMethod($generator, 'syncToModels');
    $models = $reflection->invoke($generator);

    expect($models)->toHaveCount(2)
        ->and($models[0]->id)->toBe($activity->id)
        ->and($models[1]->id)->toBe($city->id);
});

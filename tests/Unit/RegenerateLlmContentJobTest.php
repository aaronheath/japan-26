<?php

use App\Jobs\RegenerateLlmContent;
use App\Models\DayTravel;
use App\Services\LLM\Generators\TravelDomestic;
use Illuminate\Bus\Batchable;

it('uses the Batchable trait', function () {
    $traits = class_uses_recursive(RegenerateLlmContent::class);

    expect($traits)->toContain(Batchable::class);
});

it('dispatches to the redis connection and llm-regeneration queue', function () {
    $job = new RegenerateLlmContent(DayTravel::class, 1, TravelDomestic::class);

    expect($job->connection)->toBe('redis')
        ->and($job->queue)->toBe('llm-regeneration');
});

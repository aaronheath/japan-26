<?php

use App\Jobs\RegenerateLlmContent;
use Illuminate\Bus\Batchable;

it('uses the Batchable trait', function () {
    $traits = class_uses_recursive(RegenerateLlmContent::class);

    expect($traits)->toContain(Batchable::class);
});

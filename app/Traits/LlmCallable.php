<?php

namespace App\Traits;

use App\Models\LlmCall;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait LlmCallable
{
    public function llmCall(): MorphToMany
    {
        return $this
            ->morphToMany(LlmCall::class, 'llm_callable')
            ->withPivot('generator');
    }

    public function latestLlmCall(): ?LlmCall
    {
        return $this
            ->llmCall()
            ->orderBy('id', 'desc')
            ->first();
    }

    public function latestLlmCallByGenerator(string $generator): ?LlmCall
    {
        return $this
            ->llmCall()
            ->wherePivot('generator', $generator)
            ->orderBy('id', 'desc')
            ->first();
    }
}

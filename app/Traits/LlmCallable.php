<?php

namespace App\Traits;

use App\Models\LlmCall;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait LlmCallable
{
    /**
     * @return MorphToMany<LlmCall, $this>
     */
    public function llmCall(): MorphToMany
    {
        return $this
            ->morphToMany(LlmCall::class, 'llm_callable')
            ->withPivot('generator');
    }

    public function latestLlmCall(): ?LlmCall
    {
        /** @var LlmCall|null */
        return $this
            ->llmCall()
            ->orderBy('id', 'desc')
            ->first();
    }

    public function latestLlmCallByGenerator(string $generator): ?LlmCall
    {
        /** @var LlmCall|null */
        return $this
            ->llmCall()
            ->wherePivot('generator', $generator)
            ->orderBy('id', 'desc')
            ->first();
    }
}

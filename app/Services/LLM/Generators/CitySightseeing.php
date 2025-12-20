<?php

namespace App\Services\LLM\Generators;

use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Models\LlmCall;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\Response;

class CitySightseeing
{
    protected DayActivity $activity;

    protected Response $response;

    public static function make(): static
    {
        return new static();
    }

    public function activity(DayActivity $activity)
    {
        $this->activity = $activity;

        return $this;
    }

    public function generate()
    {
        $this->response = Prism::text()
            ->using(Provider::OpenRouter, 'google/gemini-2.5-flash')
//            ->using(Provider::OpenRouter, 'google/gemini-2.5-pro')
            ->withSystemPrompt(view('prompts.system.travel-agent'))
            ->withPrompt(view('prompts.generators.city-sightseeing', [
                'city' => $this->activity->useCity(),
                'date' => $this->activity->day->date->toDateString(),
            ]))
            ->withMaxTokens(5000)
            ->withClientOptions([
                'timeout' => 30,
            ])
            ->asText(function(PendingRequest $request, Response $response) {
                $this->activity->llmCall()->create([
                    'llm_model' => $request->model(),
                    'prompt_tokens' => $response->usage->promptTokens,
                    'completion_tokens' => $response->usage->completionTokens,
                    'system_prompt_view' => 'prompts.system.travel-agent',
                    'prompt_view' => 'prompts.generators.city-sightseeing',
                    'prompt_args' => [
                        'city' => $this->activity->useCity(),
                        'date' => $this->activity->day->date->toDateString(),
                    ],
                    'response' => $response->text,
                ]);
            });

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }
}

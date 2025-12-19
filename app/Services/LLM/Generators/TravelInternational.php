<?php

namespace App\Services\LLM\Generators;

use App\Models\DayTravel;
use App\Models\LlmCall;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\Response;

class TravelInternational
{
    protected DayTravel $travel;

    protected Response $response;

    public static function make(): static
    {
        return new static();
    }

    public function travel(DayTravel $travel)
    {
        $this->travel = $travel;

        return $this;
    }

    public function generate()
    {
        $this->response = Prism::text()
            ->using(Provider::OpenRouter, 'google/gemini-2.5-flash')
//            ->using(Provider::OpenRouter, 'google/gemini-2.5-pro')
            ->withSystemPrompt(view('prompts.system.travel-agent'))
            ->withPrompt(view('prompts.generators.travel-international', [
                'startCity' => $this->travel->startCity,
                'endCity' => $this->travel->endCity,
                'overnight' => $this->travel->overnight,
                'date' => $this->travel->day->date->toDateString(),
            ]))
            ->withMaxTokens(5000)
            ->withClientOptions([
                'timeout' => 30,
            ])
            ->asText(function(PendingRequest $request, Response $response) {
                $this->travel->llmCall()->create([
                    'llm_model' => $request->model(),
                    'prompt_tokens' => $response->usage->promptTokens,
                    'completion_tokens' => $response->usage->completionTokens,
                    'system_prompt_view' => 'prompts.system.travel-agent',
                    'prompt_view' => 'prompts.system.travel-international',
                    'prompt_args' => [
                        'startCity' => $this->travel->startCity->only(['id', 'name']),
                        'endCity' => $this->travel->endCity->only(['id', 'name']),
                        'overnight' => $this->travel->overnight,
                        'date' => $this->travel->day->date->toDateString(),
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

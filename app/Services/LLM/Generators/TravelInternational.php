<?php

namespace App\Services\LLM\Generators;

use App\Models\DayTravel;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
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
//            ->withProviderOptions([
//                'reasoning' => [
//                    'max_tokens' =>  5000,
//                ]
//            ])
            ->withMaxTokens(5000)
            ->withClientOptions([
                'timeout' => 30,
//                'withMaxTokens' => 5000,
            ])
            ->asText();

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }
}

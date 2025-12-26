<?php

namespace App\Services\LLM\Generators;

use App\Models\DayTravel;
use App\Models\LlmCall;
use Illuminate\Database\Eloquent\Model;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\Response;

class TravelDomestic extends BaseLlmGenerator
{
    protected DayTravel $travel;

    public function travel(DayTravel $travel)
    {
        $this->travel = $travel;

        return $this;
    }

    protected function syncToModels(): array
    {
        return [$this->travel];
    }

    protected function promptView(): string
    {
        return 'prompts.generators.travel-domestic-japan';
    }

    protected function promptArgs(): array
    {
        return [
            'startCity' => $this->travel->startCity,
            'endCity' => $this->travel->endCity,
            'overnight' => $this->travel->overnight,
            'date' => $this->travel->day->date->toDateString(),
        ];
    }
}

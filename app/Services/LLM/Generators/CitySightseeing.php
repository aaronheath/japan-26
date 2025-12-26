<?php

namespace App\Services\LLM\Generators;

use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Models\LlmCall;
use Faker\Provider\Base;
use Illuminate\Database\Eloquent\Model;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\Response;

class CitySightseeing extends BaseLlmGenerator
{
    protected DayActivity $activity;

    public function activity(DayActivity $activity)
    {
        $this->activity = $activity;

        return $this;
    }

    protected function syncToModel(): Model
    {
        return $this->activity;
    }

    protected function promptView(): string
    {
        return 'prompts.generators.city-sightseeing';
    }

    protected function promptArgs()
    {
        return [
            'city' => $this->activity->useCity(),
            'date' => $this->activity->day->date->toDateString(),
        ];
    }
}

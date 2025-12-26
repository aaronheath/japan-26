<?php

namespace App\Services\LLM\Generators;

use App\Models\City;
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
    protected City $city;

    public function activity(DayActivity $activity)
    {
        $this->activity = $activity;

        return $this;
    }

    public function city(City $city)
    {
        $this->city = $city;

        return $this;
    }

    protected function syncToModels(): array
    {
        // If a city is explicitly set, sync only that
        if(isset($this->city)) {
            return [$this->city];
        }

        return [$this->activity, $this->activity->useCity()];
    }

    protected function promptView(): string
    {
        return 'prompts.generators.city-sightseeing';
    }

    protected function promptArgs()
    {
        if(isset($this->city)) {
            return [
                'city' => $this->city,
            ];
        }

        return [
            'city' => $this->activity->useCity(),
            'date' => $this->activity->day->date->toDateString(),
        ];
    }
}

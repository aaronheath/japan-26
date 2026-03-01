<?php

namespace App\Services\LLM\Generators;

use App\Models\City;
use App\Models\DayActivity;

class Wrestling extends BaseLlmGenerator
{
    protected DayActivity $activity;

    protected City $city;

    public function activity(DayActivity $activity): static
    {
        $this->activity = $activity;

        return $this;
    }

    public function city(City $city): static
    {
        $this->city = $city;

        return $this;
    }

    protected function syncToModels(): array
    {
        if (isset($this->city)) {
            return [$this->city];
        }

        return [$this->activity, $this->activity->useCity()];
    }

    protected function promptSlug(): string
    {
        return 'wrestling';
    }

    protected function promptArgs(): array
    {
        if (isset($this->city)) {
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

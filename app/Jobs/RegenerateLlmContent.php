<?php

namespace App\Jobs;

use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Services\LLM\Generators\BaseLlmGenerator;
use App\Services\LLM\Generators\CitySightseeing;
use App\Services\LLM\Generators\TravelDomestic;
use App\Services\LLM\Generators\TravelInternational;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use InvalidArgumentException;

class RegenerateLlmContent implements ShouldQueue
{
    use Batchable, Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * @param  class-string<DayTravel|DayActivity>  $modelType
     * @param  class-string<BaseLlmGenerator>  $generatorClass
     */
    public function __construct(
        public string $modelType,
        public int $modelId,
        public string $generatorClass,
    ) {
        $this->onQueue('llm-regeneration');
    }

    public function handle(): void
    {
        $model = $this->resolveModel();
        $generator = $this->createGenerator($model);
        $generator->dontUseCache()->call();
    }

    protected function resolveModel(): DayTravel|DayActivity
    {
        return match ($this->modelType) {
            DayTravel::class => DayTravel::findOrFail($this->modelId),
            DayActivity::class => DayActivity::findOrFail($this->modelId),
            default => throw new InvalidArgumentException("Unsupported model type: {$this->modelType}"),
        };
    }

    protected function createGenerator(DayTravel|DayActivity $model): BaseLlmGenerator
    {
        if ($model instanceof DayTravel) {
            return $this->createTravelGenerator($model);
        }

        return $this->createActivityGenerator($model);
    }

    protected function createTravelGenerator(DayTravel $travel): BaseLlmGenerator
    {
        return match ($this->generatorClass) {
            TravelDomestic::class => TravelDomestic::make()->travel($travel),
            TravelInternational::class => TravelInternational::make()->travel($travel),
            default => throw new InvalidArgumentException("Unsupported generator class: {$this->generatorClass}"),
        };
    }

    protected function createActivityGenerator(DayActivity $activity): BaseLlmGenerator
    {
        return match ($this->generatorClass) {
            CitySightseeing::class => CitySightseeing::make()->activity($activity),
            default => throw new InvalidArgumentException("Unsupported generator class: {$this->generatorClass}"),
        };
    }
}

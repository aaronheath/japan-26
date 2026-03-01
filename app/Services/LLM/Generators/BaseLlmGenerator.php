<?php

namespace App\Services\LLM\Generators;

use App\Enums\LlmModels;
use App\Enums\PromptType;
use App\Models\Day;
use App\Models\LlmCall;
use App\Models\Prompt;
use App\Models\PromptVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response;

abstract class BaseLlmGenerator
{
    protected LlmModels $usedLlmProviderName;

    protected PromptVersion $usedSystemPromptVersion;

    protected PromptVersion $usedTaskPromptVersion;

    protected ?PromptVersion $usedSupplementaryPromptVersion = null;

    protected array $usedPromptArgs;

    protected bool $useCache = true;

    protected ?LlmCall $llmCall = null;

    protected ?Response $response = null;

    protected ?string $responseText = null;

    protected ?Day $day = null;

    public static function make(): static
    {
        return new static; // @phpstan-ignore new.static
    }

    public function dontUseCache(): static
    {
        $this->useCache = false;

        return $this;
    }

    public function forDay(Day $day): static
    {
        $this->day = $day;

        return $this;
    }

    public function response(): ?Response
    {
        return $this->response;
    }

    public function responseAsText(): ?string
    {
        return $this->llmCall->response;
    }

    protected function llmProviderName(): LlmModels
    {
        return LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH;
    }

    protected function systemPromptSlug(): string
    {
        return 'travel-agent-system';
    }

    abstract protected function promptSlug(): string;

    protected function promptArgs(): array
    {
        return [];
    }

    public function call(): self
    {
        $this->usedLlmProviderName = $this->llmProviderName();
        $this->usedPromptArgs = $this->promptArgs();

        $systemPrompt = Prompt::where('slug', $this->systemPromptSlug())->with('activeVersion')->firstOrFail();
        $taskPrompt = Prompt::where('slug', $this->promptSlug())->with('activeVersion')->firstOrFail();

        $this->usedSystemPromptVersion = $systemPrompt->activeVersion;
        $this->usedTaskPromptVersion = $taskPrompt->activeVersion;

        $renderedSystemPrompt = Blade::render($this->usedSystemPromptVersion->content, []);
        $renderedTaskPrompt = Blade::render($this->usedTaskPromptVersion->content, $this->usedPromptArgs);

        $renderedSupplementaryPrompt = $this->resolveSupplementaryPrompt($taskPrompt);

        $fullTaskPrompt = $renderedSupplementaryPrompt
            ? $renderedTaskPrompt."\n\n".$renderedSupplementaryPrompt
            : $renderedTaskPrompt;

        $projectedHashes = LlmCall::hashes(new LlmCall([
            'llm_provider_name' => $this->usedLlmProviderName,
            'rendered_system_prompt' => $renderedSystemPrompt,
            'rendered_task_prompt' => $renderedTaskPrompt,
            'rendered_supplementary_prompt' => $renderedSupplementaryPrompt,
        ]));

        if ($this->useCache) {
            $this->llmCall = LlmCall::query()
                ->where('overall_request_hash', $projectedHashes->overall_request_hash)
                ->first();

            if ($this->llmCall) {
                return $this;
            }
        }

        $this->response = Prism::text()
            ->using(Provider::OpenRouter, $this->usedLlmProviderName->value)
            ->withSystemPrompt($renderedSystemPrompt)
            ->withPrompt($fullTaskPrompt)
            ->withMaxTokens(5000)
            ->withClientOptions([
                'timeout' => 30,
            ])
            ->asText();

        $this->store($renderedSystemPrompt, $renderedTaskPrompt, $renderedSupplementaryPrompt);

        return $this;
    }

    /**
     * @return Model[]
     */
    abstract protected function syncToModels(): array;

    protected function resolveSupplementaryPrompt(Prompt $taskPrompt): ?string
    {
        if (! $this->day) {
            return null;
        }

        $supplementary = Prompt::query()
            ->where('type', PromptType::Supplementary)
            ->where('day_id', $this->day->id)
            ->where('parent_prompt_id', $taskPrompt->id)
            ->with('activeVersion')
            ->first();

        if (! $supplementary || ! $supplementary->activeVersion) {
            return null;
        }

        $this->usedSupplementaryPromptVersion = $supplementary->activeVersion;

        return Blade::render($this->usedSupplementaryPromptVersion->content, $this->usedPromptArgs);
    }

    protected function store(string $renderedSystemPrompt, string $renderedTaskPrompt, ?string $renderedSupplementaryPrompt = null): void
    {
        $this->llmCall = LlmCall::create([
            'llm_provider_name' => $this->usedLlmProviderName,
            'system_prompt_version_id' => $this->usedSystemPromptVersion->id,
            'task_prompt_version_id' => $this->usedTaskPromptVersion->id,
            'supplementary_prompt_version_id' => $this->usedSupplementaryPromptVersion?->id,
            'prompt_args' => $this->usedPromptArgs,
            'response' => $this->response->text,
            'rendered_system_prompt' => $renderedSystemPrompt,
            'rendered_task_prompt' => $renderedTaskPrompt,
            'rendered_supplementary_prompt' => $renderedSupplementaryPrompt,
            'prompt_tokens' => $this->response->usage->promptTokens,
            'completion_tokens' => $this->response->usage->completionTokens,
        ]);

        collect($this->syncToModels())
            ->filter()
            ->each(function (Model $model) {
                /** @phpstan-ignore method.notFound */
                $model
                    ->llmCall()
                    ->attach($this->llmCall->id, ['generator' => static::class]);
            });
    }
}

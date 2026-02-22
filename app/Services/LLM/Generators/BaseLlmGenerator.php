<?php

namespace App\Services\LLM\Generators;

use App\Enums\LlmModels;
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

    protected array $usedPromptArgs;

    protected bool $useCache = true;

    protected ?LlmCall $llmCall = null;

    protected ?Response $response = null;

    protected ?string $responseText = null;

    public static function make(): static
    {
        return new static; // @phpstan-ignore new.static
    }

    public function dontUseCache()
    {
        $this->useCache = false;

        return $this;
    }

    public function response()
    {
        return $this->response;
    }

    public function responseAsText()
    {
        return $this->llmCall->response;
    }

    protected function llmProviderName()
    {
        return LlmModels::OPEN_ROUTER_GOOGLE_GEMINI_2_5_FLASH;
    }

    protected function systemPromptSlug(): string
    {
        return 'travel-agent-system';
    }

    abstract protected function promptSlug(): string;

    protected function promptArgs()
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

        $projectedHashes = LlmCall::hashes(new LlmCall([
            'llm_provider_name' => $this->usedLlmProviderName,
            'rendered_system_prompt' => $renderedSystemPrompt,
            'rendered_task_prompt' => $renderedTaskPrompt,
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
            ->withPrompt($renderedTaskPrompt)
            ->withMaxTokens(5000)
            ->withClientOptions([
                'timeout' => 30,
            ])
            ->asText();

        $this->store($renderedSystemPrompt, $renderedTaskPrompt);

        return $this;
    }

    /**
     * @return Model[]
     */
    abstract protected function syncToModels(): array;

    protected function store(string $renderedSystemPrompt, string $renderedTaskPrompt): void
    {
        $this->llmCall = LlmCall::create([
            'llm_provider_name' => $this->usedLlmProviderName,
            'system_prompt_version_id' => $this->usedSystemPromptVersion->id,
            'task_prompt_version_id' => $this->usedTaskPromptVersion->id,
            'prompt_args' => $this->usedPromptArgs,
            'response' => $this->response->text,
            'rendered_system_prompt' => $renderedSystemPrompt,
            'rendered_task_prompt' => $renderedTaskPrompt,
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

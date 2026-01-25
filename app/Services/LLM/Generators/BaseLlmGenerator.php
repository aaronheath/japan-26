<?php

namespace App\Services\LLM\Generators;

use App\Enums\LlmModels;
use App\Models\LlmCall;
use Illuminate\Database\Eloquent\Model;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response;

abstract class BaseLlmGenerator
{
    protected LlmModels $usedLlmProviderName;

    protected string $usedSystemPromptView;

    protected string $usedPromptView;

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

    protected function systemPromptView()
    {
        return 'prompts.system.travel-agent';
    }

    abstract protected function promptView(): string;

    protected function promptArgs()
    {
        return [];
    }

    public function call(): self
    {
        $this->usedLlmProviderName = $this->llmProviderName();
        $this->usedSystemPromptView = $this->systemPromptView();
        $this->usedPromptView = $this->promptView();
        $this->usedPromptArgs = $this->promptArgs();

        $projectedHashes = LlmCall::hashes(new LlmCall([
            'llm_provider_name' => $this->usedLlmProviderName,
            'system_prompt_view' => $this->usedSystemPromptView,
            'prompt_view' => $this->usedPromptView,
            'prompt_args' => $this->usedPromptArgs,
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
            ->withSystemPrompt(view($this->usedSystemPromptView))
            ->withPrompt(view($this->usedPromptView, $this->usedPromptArgs))
            ->withMaxTokens(5000)
            ->withClientOptions([
                'timeout' => 30,
            ])
            ->asText();

        $this->store();

        return $this;
    }

    /**
     * @return Model[]
     */
    abstract protected function syncToModels(): array;

    protected function store()
    {
        $this->llmCall = LlmCall::create([
            'llm_provider_name' => $this->usedLlmProviderName,
            'system_prompt_view' => $this->usedSystemPromptView,
            'prompt_view' => $this->usedPromptView,
            'prompt_args' => $this->usedPromptArgs,
            'response' => $this->response->text,
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

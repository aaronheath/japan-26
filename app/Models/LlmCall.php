<?php

namespace App\Models;

use App\Casts\SerializeArrayWithModels;
use App\Enums\LlmModels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $rendered_system_prompt Virtual attribute for hash computation
 * @property string|null $rendered_task_prompt Virtual attribute for hash computation
 * @property string|null $rendered_supplementary_prompt Virtual attribute for hash computation
 */
class LlmCall extends Model
{
    /** @use HasFactory<\Database\Factories\LlmCallFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'llm_provider_name' => LlmModels::class,
            'prompt_args' => SerializeArrayWithModels::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            self::hashes($model);
            unset($model->rendered_system_prompt, $model->rendered_task_prompt, $model->rendered_supplementary_prompt);
        });

        static::updating(function ($model) {
            self::hashes($model);
            unset($model->rendered_system_prompt, $model->rendered_task_prompt, $model->rendered_supplementary_prompt);
        });
    }

    public function activities()
    {
        return $this->morphedByMany(DayActivity::class, 'llm_callable');
    }

    /**
     * @return BelongsTo<PromptVersion, $this>
     */
    public function systemPromptVersion(): BelongsTo
    {
        return $this->belongsTo(PromptVersion::class, 'system_prompt_version_id');
    }

    /**
     * @return BelongsTo<PromptVersion, $this>
     */
    public function taskPromptVersion(): BelongsTo
    {
        return $this->belongsTo(PromptVersion::class, 'task_prompt_version_id');
    }

    /**
     * @return BelongsTo<PromptVersion, $this>
     */
    public function supplementaryPromptVersion(): BelongsTo
    {
        return $this->belongsTo(PromptVersion::class, 'supplementary_prompt_version_id');
    }

    public static function hashes(LlmCall $model): LlmCall
    {
        if (! is_null($model->rendered_system_prompt)) {
            $model->system_prompt_hash = hash('sha256', $model->rendered_system_prompt);
        }

        if (! is_null($model->rendered_task_prompt)) {
            $model->prompt_hash = hash('sha256', $model->rendered_task_prompt);
        }

        if (! is_null($model->rendered_supplementary_prompt)) {
            $model->supplementary_prompt_hash = hash('sha256', $model->rendered_supplementary_prompt);
        }

        if ($model->system_prompt_hash && $model->prompt_hash) {
            $llmProviderValue = $model->llm_provider_name instanceof LlmModels
                ? $model->llm_provider_name->value
                : $model->llm_provider_name;

            $hashParts = sprintf(
                '%s---%s---%s',
                $llmProviderValue,
                $model->system_prompt_hash,
                $model->prompt_hash,
            );

            if ($model->supplementary_prompt_hash) {
                $hashParts .= '---'.$model->supplementary_prompt_hash;
            }

            $model->overall_request_hash = hash('sha256', $hashParts);
        }

        if (! is_null($model->response)) {
            $model->response_hash = hash('sha256', $model->response);
        }

        return $model;
    }
}

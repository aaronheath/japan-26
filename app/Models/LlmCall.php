<?php

namespace App\Models;

use App\Casts\SerializeArrayWithModels;
use App\Enums\LlmModels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected static function booted()
    {
        static::creating(function ($model) {
            $model = self::hashes($model);

            return $model;
        });

        static::updating(function ($model) {
            $model = self::hashes($model);

            return $model;
        });
    }

    public function activities()
    {
        return $this->morphyByMany(DayActivity::class, 'llm_callable');
    }

    public static function hashes(LlmCall $model)
    {
        ray($model->toArray());

        if (! is_null($model->system_prompt_view)) {
            $model->system_prompt_hash = hash('sha256', view($model->system_prompt_view));
        }

        if (! is_null($model->prompt_view)) {
            $model->prompt_hash = hash('sha256', view($model->prompt_view, $model->prompt_args));
        }

        //        if(! is_null($model->prompt_args)) {
        //            $model->prompt_args_hash = hash('sha256', json_encode($model->prompt_args));
        //        }

        if ($model->system_prompt_hash && $model->prompt_hash) {
            $model->overall_request_hash = hash('sha256', sprintf(
                '%s---%s---%s',
                $model->llm_provider_name->value,
                $model->system_prompt_hash,
                $model->prompt_hash,
            ));
        }

        if (! is_null($model->response)) {
            $model->response_hash = hash('sha256', $model->response);
        }

        return $model;
    }
}

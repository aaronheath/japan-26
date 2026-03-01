<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateFromDayRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in(['travel', 'activity']),
            ],
            'model_id' => [
                'required',
                'integer',
            ],
            'task_prompt_slug' => [
                'required',
                'exists:prompts,slug',
            ],
            'task_prompt_content' => [
                'nullable',
                'string',
            ],
            'supplementary_content' => [
                'nullable',
                'string',
            ],
        ];
    }
}

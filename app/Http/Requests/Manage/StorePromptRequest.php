<?php

namespace App\Http\Requests\Manage;

use App\Enums\PromptType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePromptRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('prompts')],
            'description' => ['nullable', 'string'],
            'type' => ['required', new Enum(PromptType::class)],
            'content' => ['required', 'string'],
            'system_prompt_id' => ['nullable', 'required_if:type,task', Rule::exists('prompts', 'id')],
        ];
    }
}

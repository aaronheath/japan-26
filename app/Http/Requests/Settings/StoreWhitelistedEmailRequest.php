<?php

namespace App\Http\Requests\Settings;

use App\Models\WhitelistedEmail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWhitelistedEmailRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:strict,spoof',
                'max:255',
                Rule::unique(WhitelistedEmail::class),
            ],
        ];
    }
}

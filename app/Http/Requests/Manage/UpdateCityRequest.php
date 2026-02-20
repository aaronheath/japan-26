<?php

namespace App\Http\Requests\Manage;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country_id' => [
                'required',
                'exists:countries,id',
            ],
            'state_id' => [
                'nullable',
                'exists:states,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'population' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'timezone' => [
                'required',
                'string',
                'timezone:all',
            ],
        ];
    }
}

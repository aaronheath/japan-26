<?php

namespace App\Http\Requests\Manage;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'addressable_type' => [
                'nullable',
                'string',
            ],
            'addressable_id' => [
                'nullable',
                'integer',
            ],
            'country_id' => [
                'required',
                'exists:countries,id',
            ],
            'state_id' => [
                'nullable',
                'exists:states,id',
            ],
            'city_id' => [
                'required',
                'exists:cities,id',
            ],
            'postcode' => [
                'required',
                'string',
                'max:20',
            ],
            'line_1' => [
                'required',
                'string',
                'max:255',
            ],
            'line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'line_3' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}

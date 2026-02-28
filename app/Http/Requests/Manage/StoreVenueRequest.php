<?php

namespace App\Http\Requests\Manage;

use App\Enums\VenueType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVenueRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'city_id' => [
                'required',
                'exists:cities,id',
            ],
            'type' => [
                'required',
                Rule::enum(VenueType::class),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'address_mode' => [
                'required',
                'in:select,new',
            ],
            'address_id' => [
                'required_if:address_mode,select',
                'nullable',
                'exists:address,id',
            ],
            'address.country_id' => [
                'required_if:address_mode,new',
                'nullable',
                'exists:countries,id',
            ],
            'address.state_id' => [
                'nullable',
                'exists:states,id',
            ],
            'address.city_id' => [
                'required_if:address_mode,new',
                'nullable',
                'exists:cities,id',
            ],
            'address.postcode' => [
                'required_if:address_mode,new',
                'nullable',
                'string',
                'max:20',
            ],
            'address.line_1' => [
                'required_if:address_mode,new',
                'nullable',
                'string',
                'max:255',
            ],
            'address.line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address.line_3' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address.latitude' => [
                'nullable',
                'numeric',
            ],
            'address.longitude' => [
                'nullable',
                'numeric',
            ],
        ];
    }
}

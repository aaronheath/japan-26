<?php

namespace App\Http\Requests\Manage;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDayAccommodationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'day_id' => [
                'required',
                'exists:days,id',
                Rule::unique('day_accommodations')->ignore($this->route('accommodation')),
            ],
            'venue_id' => [
                'required',
                'exists:venues,id',
            ],
        ];
    }
}

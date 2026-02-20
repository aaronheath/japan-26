<?php

namespace App\Http\Requests\Manage;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDayTravelRequest extends FormRequest
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
                Rule::unique('day_travels')->ignore($this->route('travel')),
            ],
            'start_city_id' => [
                'required',
                'exists:cities,id',
            ],
            'end_city_id' => [
                'required',
                'exists:cities,id',
            ],
            'overnight' => [
                'required',
                'boolean',
            ],
        ];
    }
}

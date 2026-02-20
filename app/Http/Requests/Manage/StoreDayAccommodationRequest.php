<?php

namespace App\Http\Requests\Manage;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDayAccommodationRequest extends FormRequest
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
                'unique:day_accommodations,day_id',
            ],
            'venue_id' => [
                'required',
                'exists:venues,id',
            ],
        ];
    }
}

<?php

namespace App\Http\Requests\Manage;

use App\Enums\DayActivities;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDayActivityRequest extends FormRequest
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
            ],
            'venue_id' => [
                'nullable',
                'exists:venues,id',
            ],
            'city_id' => [
                'nullable',
                'exists:cities,id',
            ],
            'type' => [
                'required',
                Rule::enum(DayActivities::class),
            ],
        ];
    }
}

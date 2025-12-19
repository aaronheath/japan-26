<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayTravel extends Model
{
    protected $table = 'day_travels';

    /** @use HasFactory<\Database\Factories\DayTravelFactory> */
    use HasFactory;

    public function day(): BelongsTo
    {
        return $this->belongsTo(Day::class);
    }

    public function startCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'start_city_id');
    }

    public function endCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'end_city_id');
    }

    public function llmCall()
    {
        return $this->morphOne(LLMCall::class, 'llm_callable');
    }
}

<?php

namespace App\Models;

use App\Enums\DayActivities;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayActivity extends Model
{
    /** @use HasFactory<\Database\Factories\DayTravelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => DayActivities::class,
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(Day::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}

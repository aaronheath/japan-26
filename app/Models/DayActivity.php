<?php

namespace App\Models;

use App\Enums\DayActivities;
use App\Traits\LlmCallable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property DayActivities $type
 */
class DayActivity extends Model
{
    /** @use HasFactory<\Database\Factories\DayTravelFactory> */
    use HasFactory;

    use LlmCallable;

    protected function casts(): array
    {
        return [
            'type' => DayActivities::class,
        ];
    }

    /**
     * @return BelongsTo<Day, $this>
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(Day::class);
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function useCity(): ?City
    {
        return $this->city ?? $this->venue->city ?? $this->inferCityForDay($this->day);
    }

    protected function inferCityForDay(Day $day): ?City
    {
        // If there's travel on this day, return the appropriate city
        if ($day->travel) {
            return $day->travel->overnight
                ? $day->travel->startCity
                : $day->travel->endCity;
        }

        // Otherwise find the most recent day that had travel and return the end city of that travel
        /** @var Day|null $mostRecentDayWithTravel */
        $mostRecentDayWithTravel = $day
            ->version
            ->days()
            ->where('number', '<', $day->number)
            ->whereHas('travel')
            ->orderBy('number', 'desc')
            ->first();

        return is_null($mostRecentDayWithTravel)
            ? null
            : $mostRecentDayWithTravel->travel?->endCity;
    }
}

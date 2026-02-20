<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property Carbon $date
 */
class Day extends Model
{
    /** @use HasFactory<\Database\Factories\DayFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<ProjectVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ProjectVersion::class, 'project_version_id');
    }

    /**
     * @return HasOne<DayTravel, $this>
     */
    public function travel(): HasOne
    {
        return $this->hasOne(DayTravel::class);
    }

    /**
     * @return HasOne<DayAccommodation, $this>
     */
    public function accommodation(): HasOne
    {
        return $this->hasOne(DayAccommodation::class);
    }

    /**
     * @return HasMany<DayActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DayActivity::class);
    }
}

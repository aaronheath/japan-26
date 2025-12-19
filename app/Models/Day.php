<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Day extends Model
{
    /** @use HasFactory<\Database\Factories\DayFactory> */
    use HasFactory;

    protected function casts()
    {
        return [
            'date' => 'date',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProjectVersion::class);
    }

    public function travel(): HasOne
    {
        return $this->hasOne(DayTravel::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DayActivity::class);
    }
}

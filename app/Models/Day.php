<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Day extends Model
{
    /** @use HasFactory<\Database\Factories\DayFactory> */
    use HasFactory;

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProjectVersion::class);
    }

    public function travel(): HasOne
    {
        return $this->hasOne(DayTravel::class);
    }
}

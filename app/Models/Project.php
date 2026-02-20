<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon $start_date
 * @property Carbon $end_date
 */
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return HasMany<ProjectVersion, $this>
     */
    public function version(): HasMany
    {
        return $this->hasMany(ProjectVersion::class);
    }

    /**
     * @return HasMany<ProjectVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ProjectVersion::class);
    }

    public function latestVersion(): ?ProjectVersion
    {
        /** @var ProjectVersion|null */
        return $this->version()->orderBy('id', 'desc')->first();
    }

    public function duration(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date);
    }
}

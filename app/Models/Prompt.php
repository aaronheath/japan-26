<?php

namespace App\Models;

use App\Enums\PromptType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prompt extends Model
{
    /** @use HasFactory<\Database\Factories\PromptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => PromptType::class,
        ];
    }

    /**
     * @return HasMany<PromptVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PromptVersion::class);
    }

    /**
     * @return BelongsTo<PromptVersion, $this>
     */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(PromptVersion::class, 'active_version_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function systemPrompt(): BelongsTo
    {
        return $this->belongsTo(self::class, 'system_prompt_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function taskPrompts(): HasMany
    {
        return $this->hasMany(self::class, 'system_prompt_id');
    }

    /**
     * @return BelongsTo<Day, $this>
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(Day::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parentPrompt(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_prompt_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function supplementaryPrompts(): HasMany
    {
        return $this->hasMany(self::class, 'parent_prompt_id');
    }
}

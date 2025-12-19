<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LlmCall extends Model
{
    /** @use HasFactory<\Database\Factories\LlmCallFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'prompt_args' => 'array',
        ];
    }
}

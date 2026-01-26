<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhitelistedEmail extends Model
{
    /** @use HasFactory<\Database\Factories\WhitelistedEmailFactory> */
    use HasFactory;

    protected $fillable = [
        'email',
    ];

    public static function isWhitelisted(string $email): bool
    {
        return static::query()
            ->where('email', strtolower($email))
            ->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayTravel extends Model
{
    protected $table = 'day_travels';

    /** @use HasFactory<\Database\Factories\DayTravelFactory> */
    use HasFactory;
}

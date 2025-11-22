<?php

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Country::class)->index();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Country::class)->index();
            $table->foreignIdFor(State::class)->index()->nullable();
            $table->string('name');
            $table->unsignedBigInteger('population');
            $table->string('timezone');
            $table->timestamps();
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(City::class)->index();
            $table->string('type'); // wrestling-venue, hotel, airport, train station
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('address', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');
            $table->foreignIdFor(Country::class)->index();
            $table->foreignIdFor(State::class)->index()->nullable();
            $table->foreignIdFor(City::class)->index();
            $table->string('postcode')->nullable();
            $table->string('line_1');
            $table->string('line_2')->nullable();
            $table->string('line_3')->nullable();
            $table->timestamps();
        });
    }
};

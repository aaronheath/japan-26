<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('project_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class);
            $table->timestamps();
        });

        Schema::create('days', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProjectVersion::class);
            $table->unsignedTinyInteger('number');
            $table->timestamps();
        });

        Schema::create('day_accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Day::class);
            $table->foreignIdFor(\App\Models\Venue::class);
            $table->timestamps();
        });

        Schema::create('day_travels', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Day::class);
            $table->foreignIdFor(\App\Models\City::class, 'start_city_id');
            $table->foreignIdFor(\App\Models\City::class, 'end_city_id');
            $table->timestamps();
        });
    }
};

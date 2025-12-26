<?php

use App\Models\City;
use App\Models\Country;
use App\Models\Day;
use App\Models\LlmCall;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\State;
use App\Models\Venue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_calls', function (Blueprint $table) {
            $table->id();
//            $table->morphs('llm_callable');
            $table->string('llm_provider_name');
            $table->text('system_prompt_view');
            $table->text('prompt_view');
//            $table->string('prompt_args_hash', 64)->nullable();
            $table->mediumText('prompt_args');
            $table->text('response');
            $table->string('overall_request_hash', 64)->index();
            $table->string('system_prompt_hash', 64);
            $table->string('prompt_hash', 64);
            $table->string('response_hash', 64);
            $table->unsignedBigInteger('prompt_tokens');
            $table->unsignedBigInteger('completion_tokens');
            $table->timestamps();
        });

        Schema::create('llm_callables', function (Blueprint $table) {
            $table->foreignIdFor(LlmCall::class);
            $table->morphs('llm_callable');
            $table->string('generator');
        });

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
            $table->unsignedBigInteger('population')->nullable();
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

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('start_date');
            $table->date('end_date');
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
            $table->date('date');
            $table->unsignedTinyInteger('number');
            $table->timestamps();
        });

        Schema::create('day_travels', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Day::class);
            $table->foreignIdFor(City::class, 'start_city_id');
            $table->foreignIdFor(City::class, 'end_city_id');
            $table->boolean('overnight')->default(false);
            $table->timestamps();
        });

        Schema::create('day_accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Day::class);
            $table->foreignIdFor(Venue::class);
            $table->timestamps();
        });

        Schema::create('day_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Day::class);
            $table->foreignIdFor(Venue::class)->nullable();
            $table->foreignIdFor(City::class)->nullable();
            $table->string('type');
            $table->timestamps();
        });
    }
};

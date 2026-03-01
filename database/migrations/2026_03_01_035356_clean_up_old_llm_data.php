<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('llm_callables')->truncate();
        DB::table('llm_calls')->truncate();
        DB::table('llm_regeneration_batches')->truncate();

        $citySightseeing = DB::table('prompts')->where('slug', 'city-sightseeing')->first();

        if ($citySightseeing) {
            DB::table('prompts')->where('id', $citySightseeing->id)->update(['active_version_id' => null]);
            DB::table('prompt_versions')->where('prompt_id', $citySightseeing->id)->delete();
            DB::table('prompts')->where('id', $citySightseeing->id)->delete();
        }
    }

    public function down(): void {}
};

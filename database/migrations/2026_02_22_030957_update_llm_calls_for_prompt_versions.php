<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llm_calls', function (Blueprint $table) {
            $table->dropColumn(['system_prompt_view', 'prompt_view']);
        });

        Schema::table('llm_calls', function (Blueprint $table) {
            $table->foreignId('system_prompt_version_id')
                ->nullable()
                ->after('llm_provider_name')
                ->constrained('prompt_versions');

            $table->foreignId('task_prompt_version_id')
                ->nullable()
                ->after('system_prompt_version_id')
                ->constrained('prompt_versions');
        });
    }

    public function down(): void
    {
        Schema::table('llm_calls', function (Blueprint $table) {
            $table->dropForeign(['system_prompt_version_id']);
            $table->dropForeign(['task_prompt_version_id']);
            $table->dropColumn(['system_prompt_version_id', 'task_prompt_version_id']);
        });

        Schema::table('llm_calls', function (Blueprint $table) {
            $table->text('system_prompt_view')->after('llm_provider_name');
            $table->text('prompt_view')->after('system_prompt_view');
        });
    }
};

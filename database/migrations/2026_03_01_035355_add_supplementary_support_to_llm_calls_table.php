<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llm_calls', function (Blueprint $table) {
            $table->foreignId('supplementary_prompt_version_id')
                ->nullable()
                ->after('task_prompt_version_id')
                ->constrained('prompt_versions')
                ->nullOnDelete();
            $table->string('supplementary_prompt_hash', 64)->nullable()->after('prompt_hash');
        });
    }

    public function down(): void
    {
        Schema::table('llm_calls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplementary_prompt_version_id');
            $table->dropColumn('supplementary_prompt_hash');
        });
    }
};

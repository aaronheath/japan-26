<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->foreignId('day_id')->nullable()->after('system_prompt_id')->constrained('days')->nullOnDelete();
            $table->foreignId('parent_prompt_id')->nullable()->after('day_id')->constrained('prompts')->nullOnDelete();
            $table->unique(['day_id', 'parent_prompt_id']);
        });
    }

    public function down(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->dropUnique(['day_id', 'parent_prompt_id']);
            $table->dropConstrainedForeignId('parent_prompt_id');
            $table->dropConstrainedForeignId('day_id');
        });
    }
};

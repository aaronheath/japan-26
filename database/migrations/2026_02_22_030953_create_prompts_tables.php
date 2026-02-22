<?php

use App\Models\Prompt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Prompt::class)->index();
            $table->unsignedInteger('version');
            $table->longText('content');
            $table->text('change_notes')->nullable();
            $table->timestamps();

            $table->unique(['prompt_id', 'version']);
        });

        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type');
            $table->foreignIdFor(Prompt::class, 'system_prompt_id')->nullable()->index();
            $table->foreignId('active_version_id')->nullable()->constrained('prompt_versions');
            $table->timestamps();
        });

        Schema::table('prompt_versions', function (Blueprint $table) {
            $table->foreign('prompt_id')->references('id')->on('prompts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prompt_versions', function (Blueprint $table) {
            $table->dropForeign(['prompt_id']);
        });

        Schema::dropIfExists('prompts');
        Schema::dropIfExists('prompt_versions');
    }
};

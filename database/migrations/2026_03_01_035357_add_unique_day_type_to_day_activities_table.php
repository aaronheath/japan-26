<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_activities', function (Blueprint $table) {
            $table->unique(['day_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('day_activities', function (Blueprint $table) {
            $table->dropUnique(['day_id', 'type']);
        });
    }
};
